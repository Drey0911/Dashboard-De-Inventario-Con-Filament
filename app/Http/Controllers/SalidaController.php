<?php

namespace App\Http\Controllers;

use App\Models\Salida;
use App\Models\Producto;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\SalidasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SalidaController extends Controller
{
    public function index(Request $request)
    {
        $query = Salida::with(['producto', 'cliente', 'usuario']);
        
        // Filtros
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        
        if ($request->filled('tipo_salida')) {
            $query->where('tipo_salida', $request->tipo_salida);
        }
        
        $salidas = $query->orderBy('fecha_salida', 'desc')
                         ->paginate(20);
        
        $productos = Producto::where('activo', true)->get();
        $clientes = Cliente::where('activo', true)->get();
        
        $tiposSalida = [
            'venta' => 'Venta',
            'devolucion' => 'Devolución',
            'muestra' => 'Muestra',
            'perdida' => 'Pérdida',
            'daño' => 'Daño',
            'ajuste' => 'Ajuste',
            'otro' => 'Otro',
        ];
        
        return view('salidas.index', compact('salidas', 'productos', 'clientes', 'tiposSalida'));
    }

    public function create()
    {
        $productos = Producto::where('activo', true)->get();
        $clientes = Cliente::where('activo', true)->get();
        
        $tiposSalida = [
            'venta' => 'Venta',
            'devolucion' => 'Devolución',
            'muestra' => 'Muestra',
            'perdida' => 'Pérdida',
            'daño' => 'Daño',
            'ajuste' => 'Ajuste',
            'otro' => 'Otro',
        ];
        
        return view('salidas.create', compact('productos', 'clientes', 'tiposSalida'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0|decimal:0,2',
            'fecha_salida' => 'required|date',
            'numero_factura' => 'nullable|string|max:100',
            'tipo_salida' => 'required|in:venta,devolucion,muestra,perdida,daño,ajuste,otro',
            'descripcion' => 'nullable|string',
        ]);

        // Verificar stock disponible
        $producto = Producto::find($validated['producto_id']);
        if ($producto->stock < $validated['cantidad']) {
            return back()->withErrors([
                'cantidad' => 'Stock insuficiente. Stock disponible: ' . $producto->stock
            ])->withInput();
        }

        // Calcular precio total
        $validated['precio_total'] = $validated['cantidad'] * $validated['precio_unitario'];
        $validated['user_id'] = Auth::id();
        $validated['activo'] = true;

        // Crear salida
        $salida = Salida::create($validated);

        // Actualizar stock del producto (disminuir)
        $producto->decrement('stock', $validated['cantidad']);

        return redirect()->route('salidas.index')
            ->with('success', 'Salida registrada exitosamente. Stock actualizado.');
    }

    public function show(Salida $salida)
    {
        $salida->load(['producto', 'cliente', 'usuario']);
        return view('salidas.show', compact('salida'));
    }

    public function edit(Salida $salida)
    {
        // No permitir edición si ya pasó mucho tiempo
        if (Carbon::parse($salida->created_at)->diffInDays(now()) > 7) {
            return redirect()->route('salidas.index')
                ->with('error', 'No se puede editar una salida con más de 7 días.');
        }

        $productos = Producto::where('activo', true)->get();
        $clientes = Cliente::where('activo', true)->get();
        
        $tiposSalida = [
            'venta' => 'Venta',
            'devolucion' => 'Devolución',
            'muestra' => 'Muestra',
            'perdida' => 'Pérdida',
            'daño' => 'Daño',
            'ajuste' => 'Ajuste',
            'otro' => 'Otro',
        ];
        
        return view('salidas.edit', compact('salida', 'productos', 'clientes', 'tiposSalida'));
    }

    public function update(Request $request, Salida $salida)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cliente_id' => 'nullable|exists:clientes,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0|decimal:0,2',
            'fecha_salida' => 'required|date',
            'numero_factura' => 'nullable|string|max:100',
            'tipo_salida' => 'required|in:venta,devolucion,muestra,perdida,daño,ajuste,otro',
            'descripcion' => 'nullable|string',
        ]);

        // Calcular nuevo precio total
        $validated['precio_total'] = $validated['cantidad'] * $validated['precio_unitario'];

        // Manejo de stock
        $diferencia = $validated['cantidad'] - $salida->cantidad;
        
        if ($salida->producto_id != $validated['producto_id']) {
            // Si cambió de producto, restaurar stock del producto anterior
            $productoAnterior = Producto::find($salida->producto_id);
            $productoAnterior->increment('stock', $salida->cantidad);
            
            // Verificar stock en nuevo producto
            $productoNuevo = Producto::find($validated['producto_id']);
            if ($productoNuevo->stock < $validated['cantidad']) {
                return back()->withErrors([
                    'cantidad' => 'Stock insuficiente en el nuevo producto. Stock disponible: ' . $productoNuevo->stock
                ])->withInput();
            }
            
            // Disminuir stock del nuevo producto
            $productoNuevo->decrement('stock', $validated['cantidad']);
        } else {
            // Mismo producto, ajustar diferencia
            $producto = Producto::find($validated['producto_id']);
            
            if ($diferencia > 0) {
                // Verificar si hay suficiente stock para la diferencia
                if ($producto->stock < $diferencia) {
                    return back()->withErrors([
                        'cantidad' => 'Stock insuficiente para aumentar la cantidad. Stock disponible: ' . $producto->stock
                    ])->withInput();
                }
                $producto->decrement('stock', $diferencia);
            } elseif ($diferencia < 0) {
                // Restaurar stock por la diferencia negativa
                $producto->increment('stock', abs($diferencia));
            }
        }

        $salida->update($validated);

        return redirect()->route('salidas.index')
            ->with('success', 'Salida actualizada exitosamente.');
    }

    public function destroy(Salida $salida)
    {
        // Restaurar stock antes de eliminar
        $producto = $salida->producto;
        $producto->increment('stock', $salida->cantidad);

        $salida->delete();

        return redirect()->route('salidas.index')
            ->with('success', 'Salida eliminada exitosamente. Stock restaurado.');
    }

    public function exportExcel(Request $request)
    {
        $query = Salida::with(['producto', 'cliente', 'usuario']);
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('tipo_salida')) {
            $query->where('tipo_salida', $request->tipo_salida);
        }
        
        return Excel::download(new SalidasExport($query->get()), 'salidas.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = Salida::with(['producto', 'cliente', 'usuario']);
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('tipo_salida')) {
            $query->where('tipo_salida', $request->tipo_salida);
        }
        
        $salidas = $query->orderBy('fecha_salida', 'desc')->get();
        
        $totalSalidas = $salidas->count();
        $totalCantidad = $salidas->sum('cantidad');
        $totalValor = $salidas->sum('precio_total');
        
        // Separar por tipo
        $ventas = $salidas->where('tipo_salida', 'venta');
        $totalVentas = $ventas->sum('precio_total');
        
        $pdf = Pdf::loadView('salidas.pdf', compact('salidas', 'totalSalidas', 'totalCantidad', 'totalValor', 'totalVentas'));
        return $pdf->download('salidas.pdf');
    }

    public function estadisticas()
    {
        $mesActual = now()->month;
        $anoActual = now()->year;
        
        // Estadísticas del mes actual
        $salidasMesActual = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->count();
            
        $ventasMesActual = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->where('tipo_salida', 'venta')
            ->count();
            
        $cantidadMesActual = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->sum('cantidad');
            
        $valorMesActual = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->sum('precio_total');
            
        $valorVentasMesActual = Salida::whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->where('tipo_salida', 'venta')
            ->sum('precio_total');
        
        // Mes anterior
        $mesAnterior = now()->subMonth()->month;
        $anoAnterior = now()->subMonth()->year;
        
        $salidasMesAnterior = Salida::whereMonth('fecha_salida', $mesAnterior)
            ->whereYear('fecha_salida', $anoAnterior)
            ->count();
            
        $ventasMesAnterior = Salida::whereMonth('fecha_salida', $mesAnterior)
            ->whereYear('fecha_salida', $anoAnterior)
            ->where('tipo_salida', 'venta')
            ->sum('precio_total');
        
        // Productos más vendidos
        $productosTop = Salida::select('producto_id', DB::raw('SUM(cantidad) as total_cantidad'))
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_cantidad')
            ->limit(10)
            ->get();
            
        // Clientes más frecuentes
        $clientesTop = Salida::select('cliente_id', DB::raw('COUNT(*) as total_salidas'))
            ->with('cliente')
            ->whereNotNull('cliente_id')
            ->groupBy('cliente_id')
            ->orderByDesc('total_salidas')
            ->limit(10)
            ->get();
        
        // Distribución por tipo
        $distribucionTipo = Salida::select('tipo_salida', DB::raw('COUNT(*) as cantidad'))
            ->groupBy('tipo_salida')
            ->get();
            
        // Ventas por día del mes actual
        $ventasPorDia = Salida::select(
                DB::raw('DAY(fecha_salida) as dia'),
                DB::raw('COUNT(*) as cantidad'),
                DB::raw('SUM(precio_total) as valor')
            )
            ->whereMonth('fecha_salida', $mesActual)
            ->whereYear('fecha_salida', $anoActual)
            ->where('tipo_salida', 'venta')
            ->groupBy(DB::raw('DAY(fecha_salida)'))
            ->orderBy('dia')
            ->get();
        
        return view('salidas.estadisticas', compact(
            'salidasMesActual',
            'ventasMesActual',
            'cantidadMesActual',
            'valorMesActual',
            'valorVentasMesActual',
            'salidasMesAnterior',
            'ventasMesAnterior',
            'productosTop',
            'clientesTop',
            'distribucionTipo',
            'ventasPorDia'
        ));
    }

    public function reporteVentas(Request $request)
    {
        $query = Salida::with(['producto', 'cliente'])
            ->where('tipo_salida', 'venta');
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_salida', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_salida', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }
        
        $ventas = $query->orderBy('fecha_salida', 'desc')
                        ->paginate(20);
        
        $clientes = Cliente::where('activo', true)->get();
        
        // Totales
        $totalVentas = $ventas->total();
        $totalCantidad = $ventas->sum('cantidad');
        $totalValor = $ventas->sum('precio_total');
        
        return view('salidas.reporte-ventas', compact(
            'ventas', 
            'clientes', 
            'totalVentas', 
            'totalCantidad', 
            'totalValor'
        ));
    }

    public function checkStock(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1'
        ]);
        
        $producto = Producto::find($request->producto_id);
        
        return response()->json([
            'stock_disponible' => $producto->stock,
            'suficiente' => $producto->stock >= $request->cantidad,
            'mensaje' => $producto->stock >= $request->cantidad 
                ? 'Stock suficiente' 
                : 'Stock insuficiente. Disponible: ' . $producto->stock
        ]);
    }
}