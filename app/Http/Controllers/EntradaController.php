<?php

namespace App\Http\Controllers;

use App\Models\Entrada;
use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exports\EntradasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class EntradaController extends Controller
{
    public function index(Request $request)
    {
        $query = Entrada::with(['producto', 'proveedor', 'usuario']);
        
        // Filtros
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_entrada', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_entrada', '<=', $request->fecha_fin);
        }
        
        if ($request->filled('producto_id')) {
            $query->where('producto_id', $request->producto_id);
        }
        
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }
        
        $entradas = $query->orderBy('fecha_entrada', 'desc')
                         ->paginate(20);
        
        $productos = Producto::where('activo', true)->get();
        $proveedores = Proveedor::where('activo', true)->get();
        
        return view('entradas.index', compact('entradas', 'productos', 'proveedores'));
    }

    public function create()
    {
        $productos = Producto::where('activo', true)->get();
        $proveedores = Proveedor::where('activo', true)->get();
        return view('entradas.create', compact('productos', 'proveedores'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0|decimal:0,2',
            'fecha_entrada' => 'required|date',
            'numero_factura' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        // Calcular precio total
        $validated['precio_total'] = $validated['cantidad'] * $validated['precio_unitario'];
        $validated['user_id'] = Auth::id();
        $validated['activo'] = true;

        // Crear entrada
        $entrada = Entrada::create($validated);

        // Actualizar stock del producto
        $producto = Producto::find($validated['producto_id']);
        $producto->increment('stock', $validated['cantidad']);

        return redirect()->route('entradas.index')
            ->with('success', 'Entrada registrada exitosamente. Stock actualizado.');
    }

    public function show(Entrada $entrada)
    {
        $entrada->load(['producto', 'proveedor', 'usuario']);
        return view('entradas.show', compact('entrada'));
    }

    public function edit(Entrada $entrada)
    {
        // No permitir edición si ya pasó mucho tiempo
        if (Carbon::parse($entrada->created_at)->diffInDays(now()) > 7) {
            return redirect()->route('entradas.index')
                ->with('error', 'No se puede editar una entrada con más de 7 días.');
        }

        $productos = Producto::where('activo', true)->get();
        $proveedores = Proveedor::where('activo', true)->get();
        return view('entradas.edit', compact('entrada', 'productos', 'proveedores'));
    }

    public function update(Request $request, Entrada $entrada)
    {
        $validated = $request->validate([
            'producto_id' => 'required|exists:productos,id',
            'proveedor_id' => 'nullable|exists:proveedores,id',
            'cantidad' => 'required|integer|min:1',
            'precio_unitario' => 'required|numeric|min:0|decimal:0,2',
            'fecha_entrada' => 'required|date',
            'numero_factura' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
        ]);

        // Calcular nuevo precio total
        $validated['precio_total'] = $validated['cantidad'] * $validated['precio_unitario'];

        // Revertir stock anterior y aplicar nuevo
        $diferencia = $validated['cantidad'] - $entrada->cantidad;
        
        if ($entrada->producto_id != $validated['producto_id']) {
            // Si cambió de producto, revertir stock del producto anterior
            $productoAnterior = Producto::find($entrada->producto_id);
            $productoAnterior->decrement('stock', $entrada->cantidad);
            
            // Aplicar stock al nuevo producto
            $productoNuevo = Producto::find($validated['producto_id']);
            $productoNuevo->increment('stock', $validated['cantidad']);
        } else {
            // Mismo producto, solo ajustar diferencia
            $producto = Producto::find($validated['producto_id']);
            if ($diferencia > 0) {
                $producto->increment('stock', $diferencia);
            } elseif ($diferencia < 0) {
                $producto->decrement('stock', abs($diferencia));
            }
        }

        $entrada->update($validated);

        return redirect()->route('entradas.index')
            ->with('success', 'Entrada actualizada exitosamente.');
    }

    public function destroy(Entrada $entrada)
    {
        // Revertir stock antes de eliminar
        $producto = $entrada->producto;
        $producto->decrement('stock', $entrada->cantidad);

        $entrada->delete();

        return redirect()->route('entradas.index')
            ->with('success', 'Entrada eliminada exitosamente. Stock revertido.');
    }

    public function exportExcel(Request $request)
    {
        $query = Entrada::with(['producto', 'proveedor', 'usuario']);
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_entrada', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_entrada', '<=', $request->fecha_fin);
        }
        
        return Excel::download(new EntradasExport($query->get()), 'entradas.xlsx');
    }

    public function exportPdf(Request $request)
    {
        $query = Entrada::with(['producto', 'proveedor', 'usuario']);
        
        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha_entrada', '>=', $request->fecha_inicio);
        }
        
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_entrada', '<=', $request->fecha_fin);
        }
        
        $entradas = $query->orderBy('fecha_entrada', 'desc')->get();
        
        $totalEntradas = $entradas->count();
        $totalCantidad = $entradas->sum('cantidad');
        $totalValor = $entradas->sum('precio_total');
        
        $pdf = Pdf::loadView('entradas.pdf', compact('entradas', 'totalEntradas', 'totalCantidad', 'totalValor'));
        return $pdf->download('entradas.pdf');
    }

    public function estadisticas()
    {
        $mesActual = now()->month;
        $anoActual = now()->year;
        
        // Estadísticas del mes actual
        $entradasMesActual = Entrada::whereMonth('fecha_entrada', $mesActual)
            ->whereYear('fecha_entrada', $anoActual)
            ->count();
            
        $cantidadMesActual = Entrada::whereMonth('fecha_entrada', $mesActual)
            ->whereYear('fecha_entrada', $anoActual)
            ->sum('cantidad');
            
        $valorMesActual = Entrada::whereMonth('fecha_entrada', $mesActual)
            ->whereYear('fecha_entrada', $anoActual)
            ->sum('precio_total');
        
        // Mes anterior
        $mesAnterior = now()->subMonth()->month;
        $anoAnterior = now()->subMonth()->year;
        
        $entradasMesAnterior = Entrada::whereMonth('fecha_entrada', $mesAnterior)
            ->whereYear('fecha_entrada', $anoAnterior)
            ->count();
            
        // Productos más entrados
        $productosTop = Entrada::select('producto_id', DB::raw('SUM(cantidad) as total_cantidad'))
            ->with('producto')
            ->groupBy('producto_id')
            ->orderByDesc('total_cantidad')
            ->limit(10)
            ->get();
        
        return view('entradas.estadisticas', compact(
            'entradasMesActual',
            'cantidadMesActual',
            'valorMesActual',
            'entradasMesAnterior',
            'productosTop'
        ));
    }
}