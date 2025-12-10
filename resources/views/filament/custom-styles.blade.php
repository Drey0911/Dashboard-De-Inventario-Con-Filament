<style>
/* Fondo principal */
.filament-login-page,
.fi-simple-layout {
    background: linear-gradient(135deg, #0a0a0a 0%, #1a0a2e 50%, #0a0a0a 100%) !important;
    position: relative;
    overflow: hidden;
}

.filament-login-page::before,
.fi-simple-layout::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(122,45,207,0.15) 0%, transparent 70%);
    animation: pulse 8s ease-in-out infinite;
    pointer-events: none;
}

@keyframes pulse {
    0%, 100% { opacity: 0.3; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
}

.fi-simple-card {
    background: rgba(20, 20, 30, 0.85) !important;
    backdrop-filter: blur(20px) !important;
    border: 1px solid rgba(122, 45, 207, 0.2) !important;
    border-radius: 24px !important;
    box-shadow: 
        0 8px 32px rgba(0, 0, 0, 0.5),
        0 0 60px rgba(122, 45, 207, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.05) !important;
    padding: 48px 40px !important;
    position: relative;
    overflow: hidden;
}

/* Brillo superior en la tarjeta */
.fi-simple-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(122, 45, 207, 0.6), transparent);
}

/* Logo/Brand name styling */
.fi-simple-header {
    margin-bottom: 32px !important;
    text-align: center !important;
}

/* Logo personalizado */
.fi-simple-header::before {
    content: '';
    display: block;
    width: 120px;
    height: 120px;
    margin: 0 auto 24px;
    background-image: url('/storage/logo/logo-inventario.png');
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
    filter: drop-shadow(0 0 20px rgba(122, 45, 207, 0.4));
    animation: logoFloat 3s ease-in-out infinite;
}

@keyframes logoFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.fi-simple-header h1,
.fi-simple-header .fi-logo {
    color: #ffffff !important;
    text-shadow: 0 0 20px rgba(122, 45, 207, 0.5);
}

/* Labels de los inputs */
.fi-simple-label,
.fi-fo-field-wrp-label label {
    color: rgba(255, 255, 255, 0.9) !important;
    font-weight: 500 !important;
    margin-bottom: 8px !important;
}

.fi-simple-input input,
.fi-input input,
input[type="email"],
input[type="password"],
input[type="text"] {
    background: rgba(30, 30, 45, 0.6) !important;
    border: 1px solid rgba(122, 45, 207, 0.3) !important;
    border-radius: 12px !important;
    color: #ffffff !important;
    padding: 12px 16px !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.3) !important;
}

.fi-header-actions .fi-btn span,
.fi-header-actions .fi-btn svg,
.fi-ac-btn-action span,
.fi-ac-btn-action svg,
.fi-btn.fi-ac-action span,
.fi-btn.fi-ac-action svg {
    color: #ffffff !important;
    fill: #ffffff !important;
}

/* Input placeholder */
.fi-simple-input input::placeholder,
.fi-input input::placeholder {
    color: rgba(255, 255, 255, 0.4) !important;
}

.fi-simple-input input:focus,
.fi-input input:focus,
input[type="email"]:focus,
input[type="password"]:focus,
input[type="text"]:focus {
    background: rgba(40, 40, 60, 0.8) !important;
    border-color: #7A2DCF !important;
    box-shadow: 
        0 0 0 3px rgba(122, 45, 207, 0.2),
        0 0 20px rgba(122, 45, 207, 0.3),
        inset 0 2px 8px rgba(0, 0, 0, 0.3) !important;
    outline: none !important;
}

/* Botón principal */
.fi-simple-btn-primary,
.fi-btn-primary,
button[type="submit"] {
    background: linear-gradient(135deg, #7A2DCF 0%, #a855f7 100%) !important;
    border: none !important;
    border-radius: 12px !important;
    padding: 14px 24px !important;
    font-weight: 600 !important;
    color: #ffffff !important;
    box-shadow: 
        0 4px 16px rgba(122, 45, 207, 0.4),
        0 0 30px rgba(122, 45, 207, 0.2) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    position: relative;
    overflow: hidden;
}

/* Textos blancos al boton primario*/
.fi-simple-btn-primary *,
.fi-btn-label *,
.fi-btn-primary *,
button[type="submit"] *,
.fi-simple-btn-primary span,
.fi-btn-primary span,
button[type="submit"] span,
.fi-simple-btn-primary svg,
.fi-btn-primary svg,
button[type="submit"] svg {
    color: #ffffff !important;
    fill: #ffffff !important;
}

/* Efecto hover del botón */
.fi-simple-btn-primary:hover,
.fi-btn-primary:hover,
button[type="submit"]:hover {
    background: linear-gradient(135deg, #8b3de0 0%, #b565ff 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 
        0 6px 24px rgba(122, 45, 207, 0.6),
        0 0 40px rgba(122, 45, 207, 0.3) !important;
}

/* Efecto de brillo en el botón */
.fi-simple-btn-primary::before,
.fi-btn-primary::before,
button[type="submit"]::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.fi-simple-btn-primary:hover::before,
.fi-btn-primary:hover::before,
button[type="submit"]:hover::before {
    left: 100%;
}

/* Links y texto secundario */
a,
.fi-link {
    color: #a855f7 !important;
    transition: color 0.2s !important;
}

a:hover,
.fi-link:hover {
    color: #c084fc !important;
    text-shadow: 0 0 8px rgba(168, 85, 247, 0.5);
}

/* Mensajes de error */
.fi-fo-field-wrp-error-message,
.text-danger {
    color: #f87171 !important;
    font-size: 0.875rem !important;
    margin-top: 6px !important;
}

/* Checkbox y radio buttons */
input[type="checkbox"] {
    accent-color: #7A2DCF !important;
}

/* Textos generales */
.fi-simple-page p,
.fi-simple-page span {
    color: rgba(255, 255, 255, 0.8) !important;
}

/* Animación de entrada suave */
.fi-simple-card {
    animation: fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .fi-simple-card {
        padding: 32px 24px !important;
        border-radius: 20px !important;
    }
}
</style>