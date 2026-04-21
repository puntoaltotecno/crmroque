<?php 
/**
 * ARCHIVO: index.php
 */
require_once 'db.php'; 

if (isset($_GET['logout'])) { session_unset(); session_destroy(); header("Location: ./"); exit; }

$nombre_usuario = $_SESSION['user_name'] ?? 'Usuario';
$rol_usuario    = $_SESSION['user_rol'] ?? 'operador';
$user_id        = $_SESSION['user_id'] ?? 0;
$can_assign     = ($rol_usuario === 'admin' || $rol_usuario === 'colaborador');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Cobranzas - Roque</title>
    <!-- Tailwind CSS v2 - Archivo local (sin dependencia de CDN externo) -->
    <link rel="stylesheet" href="tailwind.min.css">


    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Google Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f1f5f9; color: #1e293b; }
        .custom-shadow { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .text-crm-blue { color: #0284c7; }
        .text-crm-red { color: #dc2626; }
        .bg-crm-dark-blue { background-color: #1e3a5f; }
        .bg-crm-blue { background-color: #0284c7; }
        .border-crm-active { border-bottom: 3px solid #0284c7; }
        
        .tab-active { color: #0284c7; border-bottom: 3px solid #0284c7; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        /* Badges de estados */
        .badge-promesa { background: #dbeafe; color: #1e40af; }
        .badge-no_responde { background: #ffedd5; color: #9a3412; }
        .badge-no_corresponde { background: #fee2e2; color: #991b1b; }
        .badge-llamar { background: #dcfce7; color: #166534; }
        .badge-numero_baja { background: #f1f5f9; color: #475569; }
        .badge-carta { background: #fce7f3; color: #be185d; }
        .badge-otro { background: #f3e8ff; color: #6b21a8; }
        .badge-sin_gestion { background: #f8fafc; color: #94a3b8; border: 1px solid #e2e8f0; }
        .badge-al_dia { background: #ccfbf1; color: #0f766e; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen" onclick="cerrarDropdownNoti()">

    <?php if (!$user_id): ?>
    <div class="flex items-center justify-center min-h-screen p-6 bg-slate-100">
        <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl w-full max-w-sm border border-slate-200 text-center">
            <div class="bg-blue-600 w-20 h-20 rounded-[1.8rem] mx-auto mb-4 flex items-center justify-center text-white text-4xl font-extrabold italic shadow-2xl shadow-blue-200">R</div>
            <h1 class="text-2xl font-black text-slate-800 tracking-tighter uppercase mb-10">CRM Roque</h1>
            
            <form id="loginForm" class="space-y-4" onsubmit="handleLogin(event)">
                <input type="text" name="usuario" placeholder="Email" required class="w-full px-6 py-4 bg-slate-50 border rounded-2xl outline-none font-semibold">
                <input type="password" name="clave" placeholder="Contraseña" required class="w-full px-6 py-4 bg-slate-50 border rounded-2xl outline-none font-semibold">
                <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black uppercase shadow-xl hover:bg-blue-700 transition">Entrar</button>
            </form>
            <div id="lError" class="mt-6 text-red-600 text-xs font-bold hidden bg-red-50 py-3 rounded-xl"></div>
        </div>
    </div>

    <script>
        async function handleLogin(e) {
            e.preventDefault();
            const btn = e.target.querySelector('button[type="submit"]');
            const ogText = btn.innerText;
            btn.disabled = true;
            btn.innerText = 'Ingresando...';
            try {
                const res = await fetch('login.php', { method: 'POST', body: new FormData(e.target) });
                const d = await res.json();
                if (d.success) { location.reload(); } 
                else {
                    const err = document.getElementById('lError');
                    err.innerText = d.message || 'Credenciales incorrectas.';
                    err.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerText = ogText;
                }
            } catch(ex) { alert('Error de conexión.'); btn.disabled = false; btn.innerText = ogText; }
        }
    </script>

    <?php else: ?>
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-[1440px] mx-auto px-4 h-16 flex items-center justify-between">
            <!-- Logo and Navigation -->
            <div class="flex items-center space-x-8">
                <div class="flex items-center space-x-2">
                    <img alt="CRM.ROQUE Logo" class="h-8 w-auto" src="https://lh3.googleusercontent.com/aida-public/AB6AXuBhmpThYriWMJpQlcp9Y8oU5X6DsNvP2H_Z9rz_Rv3oVlAtC5CACHVawuF6dIcIfP2zitrYuKYOGAFdg1W3PwR2sf8nCQje7kEIEvnalVTzcUe1z0In_jPQoaX_TnojUD4KZR5YlhZk_3gXuON1--PbmPQVBRAVMAsMhHJNhFA6GYXleDuzB5PicVx7Sw1E3dGFZ-lsLK1ITuqppOhhOhK_5P5ky-PptHWAd9SJuEKiC36p62H4_fl8GUXxE5-SCFkv7Q5f4nG2_3wY"/>
                    <span class="font-extrabold text-xl tracking-tight uppercase">CRM.<span class="text-gray-500">ROQUE</span></span>
                </div>
                <!-- Navegación Desktop -->
                <nav class="hidden md:flex items-center space-x-6 text-[10px] font-extrabold text-gray-400 uppercase tracking-widest">
                    <?php if($can_assign): ?>
                    <button onclick="switchTab('dashboard')" id="btn-dashboard" class="hover:text-crm-blue transition-colors">TABLERO</button>
                    <?php endif; ?>
                    <button onclick="switchTab('rendimiento')" id="btn-rendimiento" class="hover:text-crm-blue transition-colors">MI RENDIMIENTO</button>
                    <button onclick="switchTab('clientes')" id="btn-clientes" class="hover:text-crm-blue transition-colors">CARTERA ACTIVA</button>
                    <?php if($can_assign): ?>
                    <button onclick="switchTab('usuarios')" id="btn-usuarios" class="hover:text-crm-blue transition-colors">EQUIPO</button>
                    <button onclick="switchTab('reportes')" id="btn-reportes" class="hover:text-crm-blue transition-colors">REPORTES</button>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="flex items-center gap-4 md:gap-6">
                <!-- Botón de Hamburguesa Mobile -->
                <button onclick="toggleMenu()" class="md:hidden flex items-center justify-center w-8 h-8 bg-slate-100 rounded-lg text-slate-600 hover:bg-slate-200 transition active:scale-95" aria-label="Abrir menú">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>

                <!-- Botón de Comunicados -->
                <?php if($can_assign): ?>
                <button onclick="abrirModalComunicado()" class="text-xl hover:scale-110 transition origin-center" title="Lanzar Aviso a Operadores">📣</button>
                <?php endif; ?>

                <div class="relative cursor-pointer flex items-center justify-center w-8 h-8 bg-slate-50 border rounded-lg hover:bg-slate-100 transition" onclick="toggleNotificaciones(event)">
                    <span class="text-lg">🔔</span>
                    <span id="noti-badge" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full shadow-sm">0</span>
                    
                    <div id="noti-dropdown" class="hidden absolute top-full right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden cursor-default" onclick="event.stopPropagation()">
                        <div class="bg-crm-dark-blue px-5 py-4 flex justify-between items-center">
                            <h4 class="text-white font-black text-xs uppercase tracking-widest">Alertas de Agenda</h4>
                            <span id="noti-count-text" class="text-slate-400 text-[10px] font-bold">0</span>
                        </div>
                        <div id="noti-list" class="max-h-[60vh] overflow-y-auto custom-scroll divide-y divide-slate-100">
                            <p class="text-center py-6 text-[10px] font-black uppercase text-slate-300">Cargando...</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center border-l pl-4 space-x-3 hidden md:flex">
                    <div class="text-right">
                        <p class="text-[10px] font-extrabold uppercase text-slate-700"><?= htmlspecialchars($nombre_usuario) ?></p>
                        <p class="text-[9px] font-bold text-gray-400 uppercase">(<?= $rol_usuario ?>)</p>
                    </div>
                </div>
                <a href="?logout=1" class="border border-gray-300 px-4 py-1.5 rounded text-[10px] font-extrabold text-gray-600 hover:bg-gray-50 uppercase shadow-sm">Salir</a>
            </div>
        </div>
    </header>

    <!-- MENÚ DRAWER (MOBILE) -->
    <div id="mobileMenu" class="fixed inset-0 z-50 hidden md:hidden">
        <!-- Overlay oscuro -->
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0" id="mobileMenuOverlay" onclick="toggleMenu()"></div>
        
        <!-- Contenedor del Drawer -->
        <div class="absolute top-0 right-0 w-64 h-full bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col" id="mobileMenuDrawer">
            <div class="p-6 border-b flex justify-between items-center bg-slate-50">
                <div class="flex items-center gap-2">
                    <div class="bg-blue-600 w-8 h-8 rounded-lg flex items-center justify-center text-white font-black italic text-sm">R</div>
                    <h3 class="text-xs font-black uppercase tracking-widest text-slate-800">Navegación</h3>
                </div>
                <button onclick="toggleMenu()" class="text-slate-400 hover:text-rose-500 w-8 h-8 flex justify-center items-center rounded-full bg-slate-200 shadow-inner group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <nav class="flex-1 flex flex-col p-4 gap-2 overflow-y-auto">
                <?php if($can_assign): ?>
                <button onclick="switchTab('dashboard'); toggleMenu();" class="flex items-center gap-4 p-4 rounded-2xl text-left font-black text-xs uppercase tracking-widest text-slate-600 hover:bg-blue-50 focus:bg-blue-50 focus:text-blue-600 hover:text-blue-600 transition h-14">
                    <span class="text-xl">📊</span> TABLERO
                </button>
                <?php endif; ?>
                <button onclick="switchTab('rendimiento'); toggleMenu();" class="flex items-center gap-4 p-4 rounded-2xl text-left font-black text-xs uppercase tracking-widest text-slate-600 hover:bg-blue-50 focus:bg-blue-50 focus:text-blue-600 hover:text-blue-600 transition h-14">
                    <span class="text-xl">🏅</span> MI RENDIMIENTO
                </button>
                <button onclick="switchTab('clientes'); toggleMenu();" class="flex items-center gap-4 p-4 rounded-2xl text-left font-black text-xs uppercase tracking-widest text-slate-600 hover:bg-blue-50 focus:bg-blue-50 focus:text-blue-600 hover:text-blue-600 transition h-14">
                    <span class="text-xl">👥</span> CARTERA ACTIVA
                </button>
                <?php if($can_assign): ?>
                <button onclick="switchTab('usuarios'); toggleMenu();" class="flex items-center gap-4 p-4 rounded-2xl text-left font-black text-xs uppercase tracking-widest text-slate-600 hover:bg-blue-50 focus:bg-blue-50 focus:text-blue-600 hover:text-blue-600 transition h-14">
                    <span class="text-xl">👨‍💻</span> EQUIPO
                </button>
                <button onclick="switchTab('reportes'); toggleMenu();" class="flex items-center gap-4 p-4 rounded-2xl text-left font-black text-xs uppercase tracking-widest text-slate-600 hover:bg-blue-50 focus:bg-blue-50 focus:text-blue-600 hover:text-blue-600 transition h-14">
                    <span class="text-xl">📈</span> REPORTES
                </button>
                <?php endif; ?>
            </nav>
            <div class="p-6 border-t bg-slate-50">
                <a href="?logout=1" class="flex items-center justify-center gap-2 p-4 rounded-2xl font-black text-xs uppercase tracking-widest text-rose-600 bg-rose-50 hover:bg-rose-100 transition w-full h-14">
                    <span class="text-lg">🚪</span> Salir
                </a>
            </div>
        </div>
    </div>

    <!-- BOTTOM NAVIGATION (OPCIONAL/IDEAL MOBILE UX) -->
    <nav class="hidden fixed bottom-0 left-0 w-full bg-white/95 backdrop-blur-md border-t border-slate-200 z-40 flex justify-around items-center px-1 py-2 pb-safe shadow-[0_-5px_15px_rgba(0,0,0,0.05)]">
        <?php if($can_assign): ?>
        <button onclick="switchTab('dashboard')" class="flex flex-col items-center justify-center w-14 h-12 text-slate-400 hover:text-blue-600 transition">
            <span class="text-lg mb-1">📊</span>
            <span class="text-[8px] font-black text-center uppercase tracking-tighter w-full truncate">TABLERO</span>
        </button>
        <?php endif; ?>
        <button onclick="switchTab('rendimiento')" class="flex flex-col items-center justify-center w-14 h-12 text-slate-400 hover:text-blue-600 transition">
            <span class="text-lg mb-1">🏅</span>
            <span class="text-[8px] font-black text-center uppercase tracking-tighter w-full truncate">RENDIM.</span>
        </button>
        <button onclick="switchTab('clientes')" class="flex flex-col items-center justify-center w-14 h-12 text-slate-400 hover:text-blue-600 transition">
            <span class="text-lg mb-1">👥</span>
            <span class="text-[8px] font-black text-center uppercase tracking-tighter w-full truncate">CARTERA</span>
        </button>
        <?php if($can_assign): ?>
        <button onclick="switchTab('usuarios')" class="flex flex-col items-center justify-center w-14 h-12 text-slate-400 hover:text-blue-600 transition">
            <span class="text-lg mb-1">👨‍💻</span>
            <span class="text-[8px] font-black text-center uppercase tracking-tighter w-full truncate">EQUIPO</span>
        </button>
        <button onclick="switchTab('reportes')" class="flex flex-col items-center justify-center w-14 h-12 text-slate-400 hover:text-blue-600 transition">
            <span class="text-lg mb-1">📈</span>
            <span class="text-[8px] font-black text-center uppercase tracking-tighter w-full truncate">REPORTES</span>
        </button>
        <?php endif; ?>
    </nav>

    <main class="max-w-7xl mx-auto p-4 md:p-8 space-y-10 pb-10">
        <div class="grid grid-cols-1 md:grid-cols-<?= $rol_usuario === 'operador' ? '2' : '4' ?> gap-4 mb-8">
            <?php if($rol_usuario !== 'operador'): ?>
            <div class="bg-white p-6 rounded-xl custom-shadow text-center">
                <h3 class="text-[10px] font-extrabold text-gray-500 mb-1 uppercase tracking-widest">Total en Calle</h3>
                <p id="stat-deuda" class="text-2xl font-extrabold text-crm-blue">$0</p>
            </div>
            <?php endif; ?>
            <div class="bg-white p-6 rounded-xl custom-shadow text-center">
                <h3 class="text-[10px] font-extrabold text-gray-500 mb-1 uppercase tracking-widest">Promesas (Activas)</h3>
                <p id="stat-promesas" class="text-3xl font-extrabold text-crm-blue">0</p>
            </div>
            <div class="bg-white p-6 rounded-xl custom-shadow text-center">
                <h3 class="text-[10px] font-extrabold text-gray-500 mb-1 uppercase tracking-widest">Clientes (Lista Actual)</h3>
                <p id="stat-filtrados" class="text-3xl font-extrabold text-crm-red">0</p>
            </div>
            <?php if($rol_usuario !== 'operador'): ?>
            <div class="bg-white p-6 rounded-xl custom-shadow text-center">
                <h3 class="text-[10px] font-extrabold text-gray-500 mb-1 uppercase tracking-widest">Total Clientes BD</h3>
                <p id="stat-total" class="text-3xl font-extrabold text-crm-blue">0</p>
            </div>
            <?php endif; ?>
        </div>

        <?php if($can_assign): ?>
        <section id="sec-dashboard" class="hidden space-y-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest">Resumen General</h3>
                <button onclick="loadDashboard()" class="text-[10px] font-extrabold uppercase text-crm-blue hover:underline transition bg-white px-4 py-2 rounded-lg border shadow-sm">↻ Actualizar Tablero</button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-crm-dark-blue p-6 rounded-xl custom-shadow relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-6xl">👥</div>
                    <p class="text-[10px] font-black text-blue-300 uppercase tracking-widest mb-1 relative z-10">Total Asignados</p>
                    <h4 id="dash-asignados" class="text-3xl font-bold text-white relative z-10">0</h4>
                </div>
                <div class="bg-white p-6 rounded-xl custom-shadow border border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Total Gestionados</p>
                    <h4 id="dash-gestionados" class="text-3xl font-bold text-crm-blue">0</h4>
                </div>
                <div class="bg-white p-6 rounded-xl custom-shadow border border-gray-100">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Promesas Logradas</p>
                    <h4 id="dash-promesas-totales" class="text-3xl font-bold text-emerald-600">0</h4>
                </div>
                <div class="bg-gradient-to-br from-blue-700 to-indigo-900 p-6 rounded-xl custom-shadow text-white">
                    <p class="text-[10px] font-black text-blue-100 uppercase tracking-widest mb-1">Cobertura</p>
                    <h4 id="dash-cobertura" class="text-3xl font-bold text-white">0%</h4>
                </div>
            </div>

            <h3 class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest mb-4 mt-8">Estado de la Cartera (Clic para filtrar)</h3>
            <div id="dash-estados" class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-3 mb-8">
                <p class="col-span-full text-center text-[10px] font-bold text-gray-300 py-4 uppercase tracking-widest">Cargando estados...</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl custom-shadow border border-gray-100 overflow-hidden p-6 md:p-8">
                        <h3 class="text-xs font-extrabold text-gray-800 uppercase tracking-wider mb-6">Métricas por Sucursal</h3>
                        <div class="overflow-x-auto custom-scroll">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b text-gray-400 font-extrabold uppercase text-[9px] tracking-widest">
                                    <tr>
                                        <th class="px-4 py-4 text-left">Sucursal</th>
                                        <th class="px-4 py-4 text-center">Total Clientes</th>
                                        <th class="px-4 py-4 text-center">Gestiones</th>
                                        <th class="px-4 py-4 text-center">% Gestionados</th>
                                        <th class="px-4 py-4 text-right">En Calle (Deuda)</th>
                                    </tr>
                                </thead>
                                <tbody id="listaSucursales" class="divide-y divide-gray-50">
                                    <tr><td colspan="5" class="text-center py-6 text-gray-400 font-bold text-xs uppercase tracking-widest">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl custom-shadow border border-gray-100 overflow-hidden p-6 md:p-8">
                        <h3 class="text-xs font-extrabold text-gray-800 uppercase tracking-wider mb-6">Rendimiento por Operador</h3>
                        <div class="overflow-x-auto custom-scroll">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 border-b text-gray-400 font-extrabold uppercase text-[9px] tracking-widest">
                                    <tr>
                                        <th class="px-4 py-5 text-left">Operador</th>
                                        <th class="px-4 py-5 text-center">Asignados</th>
                                        <th class="px-4 py-5 text-center">Gestionados</th>
                                        <th class="px-4 py-5 text-center">% Gestionados</th>
                                        <th class="px-4 py-5 text-center">Promesas</th>
                                        <th class="px-4 py-5 text-center">Efectividad</th>
                                    </tr>
                                </thead>
                                <tbody id="listaDashboard" class="divide-y divide-gray-50">
                                    <tr><td colspan="6" class="text-center py-10 text-gray-400 font-bold text-xs uppercase tracking-widest">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl custom-shadow border border-slate-200 p-6 md:p-8 flex flex-col max-h-[850px]">
                    <div class="flex items-center gap-3 mb-6 shrink-0">
                        <div class="w-2 h-2 bg-red-400 rounded-full animate-pulse"></div>
                        <h3 class="text-xs font-extrabold text-slate-700 uppercase tracking-widest">Últimas Gestiones</h3>
                    </div>
                    <div id="feedGestiones" class="flex-1 overflow-y-auto custom-scroll pr-2 space-y-3">
                        <p class="text-center text-slate-400 text-[10px] font-bold mt-10 uppercase tracking-widest">Cargando feed en vivo...</p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section id="sec-clientes" class="hidden space-y-6">
            <div class="flex flex-col gap-4 w-full">
                <!-- Buscador y Acciones -->
                <div class="flex flex-col lg:flex-row gap-4 w-full">
                    <input type="text" id="search" placeholder="Buscar por Legajo, Razón, Doc o Tel..." class="flex-1 px-4 py-3 bg-white border border-gray-200 rounded-xl text-sm font-medium outline-none shadow-sm focus:ring-4 focus:ring-crm-blue/10 transition">
                    
                    <div class="flex gap-2 w-full lg:w-auto shrink-0 mt-2 lg:mt-0">
                        <button onclick="exportarExcel()" class="flex-1 lg:flex-none bg-blue-50 text-crm-blue px-6 py-3 rounded-xl text-[10px] font-black uppercase border border-blue-100 hover:bg-blue-100 transition shadow-sm">Exportar</button>
                        
                        <?php if($rol_usuario === 'admin'): ?>
                        <button onclick="document.getElementById('csv').click()" class="flex-1 lg:flex-none bg-crm-dark-blue text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase shadow-sm hover:opacity-90 transition">Importar</button>
                        <input type="file" id="csv" class="hidden" accept=".csv" onchange="subirCSV(this)">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtros Secundarios -->
                <div class="flex flex-col md:flex-row items-center gap-3 w-full">
                    <select id="filter-estado" onchange="load(); stats();" class="w-full md:flex-1 px-4 py-3 bg-white border border-gray-200 rounded-xl text-[10px] font-black uppercase outline-none shadow-sm cursor-pointer hover:bg-gray-50">
                        <option value="">Todos los Estados</option>
                        <option value="sin_gestion">Pendiente (Sin gestión)</option>
                        <option value="al_dia">Al Día</option>
                        <option value="promesa">Promesa de Pago</option>
                        <option value="no_responde">No Responde</option>
                        <option value="no_corresponde">No Corresponde</option>
                        <option value="llamar">Llamar luego</option>
                        <option value="numero_baja">Nro de Baja</option>
                        <option value="carta">Carta</option>
                        <option value="otro">Otro</option>
                    </select>

                    <?php if($can_assign): ?>
                    <select id="filter-operador" onchange="load(); stats();" class="w-full md:flex-1 px-4 py-3 bg-white border border-gray-200 rounded-xl text-[10px] font-black uppercase outline-none shadow-sm cursor-pointer hover:bg-gray-50">
                        <option value="0">Todos los Operadores</option>
                        <option value="-1">👤 Sin Asignar</option>
                    </select>
                    <div class="w-full md:w-auto flex items-center justify-between gap-3 bg-white border border-gray-200 rounded-xl px-4 shadow-sm shrink-0" title="Límite de filas en pantalla">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest whitespace-nowrap">Filas</span>
                        <input type="number" id="filter-limit" min="1" max="500" value="200" onchange="load(); stats();"
                            class="w-16 py-3 bg-transparent text-xs font-bold text-gray-700 outline-none text-right">
                    </div>
                    <?php endif; ?>

                    <label class="w-full md:w-auto shrink-0 flex items-center justify-center space-x-2 cursor-pointer bg-red-50 px-4 py-3 rounded-xl border border-red-100 shadow-sm hover:bg-red-100 transition-colors">
                        <input type="checkbox" id="filtroMoto" class="form-checkbox h-4 w-4 text-crm-red rounded focus:ring-red-500 border-red-200" onchange="load(); stats();">
                        <span class="text-[10px] font-black uppercase tracking-widest text-red-700">Deuda Moto</span>
                    </label>
                </div>
            </div>
            
            <!-- Toggles for contact buttons -->
            <div class="flex flex-wrap items-center gap-6 bg-white px-6 py-3 border border-gray-100 rounded-xl shadow-sm mb-4">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Canales visibles:</span>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="toggleWaWeb" class="form-checkbox h-3 w-3 text-emerald-600 rounded" onchange="saveContactToggles()">
                    <span class="text-[9px] font-bold uppercase text-gray-600">🌐 WA Web</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="toggleWaApp" class="form-checkbox h-3 w-3 text-teal-600 rounded" onchange="saveContactToggles()">
                    <span class="text-[9px] font-bold uppercase text-gray-600">💻 WA App</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="toggleLlamar" class="form-checkbox h-3 w-3 text-blue-600 rounded" onchange="saveContactToggles()">
                    <span class="text-[9px] font-bold uppercase text-gray-600">📞 Llamar</span>
                </label>
                <label class="flex items-center space-x-2 cursor-pointer">
                    <input type="checkbox" id="toggleSms" class="form-checkbox h-3 w-3 text-violet-600 rounded" onchange="saveContactToggles()">
                    <span class="text-[9px] font-bold uppercase text-gray-600">✉️ SMS</span>
                </label>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden relative">
                <div class="overflow-x-auto custom-scroll">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b text-gray-400 font-extrabold uppercase text-[9px] tracking-widest">
                            <tr>
                                <?php if($can_assign): ?>
                                <th class="pl-6 pr-2 py-3 text-left"><input type="checkbox" onchange="toggleSelectAll(event)" class="w-4 h-4 rounded text-crm-blue bg-white border-gray-300"></th>
                                <?php endif; ?>
                                <th class="<?= $can_assign ? 'px-2' : 'px-6' ?> py-3 text-left whitespace-nowrap">Cliente</th>
                                <th class="px-6 py-3 text-center whitespace-nowrap">Gestión / Atraso</th>
                                <th class="px-6 py-3 text-right whitespace-nowrap">Vencido</th>
                                <th class="px-6 py-3 text-center whitespace-nowrap">Asignado</th>
                                <th class="pr-6 pl-4 py-3 text-center whitespace-nowrap">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="lista" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="sec-usuarios" class="hidden space-y-6">
            <div class="flex justify-between items-center px-2">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest">Personal Operativo</h3>
                <?php if($rol_usuario === 'admin'): ?>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('csvAsignaciones').click()" class="bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-gray-50 transition shadow-sm">Importar Asignaciones</button>
                    <input type="file" id="csvAsignaciones" class="hidden" accept=".csv" onchange="subirCSVAsignaciones(this)">
                    <button onclick="openUserModal()" class="bg-crm-blue text-white px-6 py-2.5 rounded-xl text-[10px] font-black uppercase shadow-sm hover:opacity-90 transition">Nuevo Operador</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="listaPersonal"></div>
        </section>

        <!-- ========================================================= -->
        <!-- SECCIÓN: MI RENDIMIENTO (OPERADORES Y ADMINS)             -->
        <!-- ========================================================= -->
        <!-- ========================================================= -->
        <!-- SECCIÓN: MI RENDIMIENTO (MODERNIZADO)                     -->
        <!-- ========================================================= -->
        <section id="sec-rendimiento" class="hidden space-y-6">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Left Column: Performance and Agenda -->
                <div class="flex-1 space-y-6">
                    <!-- MI RENDIMIENTO CARD -->
                    <section class="bg-crm-dark-blue text-white p-6 rounded-xl custom-shadow" data-purpose="performance-card">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider">Mi Rendimiento</h2>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                            <!-- Small Stat Cards -->
                            <div class="bg-[#2a4a75] p-3 rounded border-l-4 border-blue-500">
                                <p class="text-[9px] font-black uppercase opacity-80 mb-1">Total Asignados</p>
                                <p id="rm-asignados" class="text-2xl font-bold">0</p>
                            </div>
                            <div class="bg-[#2a4a75] p-3 rounded border-l-4 border-green-500">
                                <p class="text-[9px] font-black uppercase opacity-80 mb-1">Clientes al Día</p>
                                <p id="rm-aldia" class="text-2xl font-bold text-green-400">0</p>
                            </div>
                            <div class="bg-[#2a4a75] p-3 rounded border-l-4 border-orange-400">
                                <p class="text-[9px] font-black uppercase opacity-80 mb-1">Promesas Activas</p>
                                <p id="rm-promesas" class="text-2xl font-bold text-orange-400">0</p>
                            </div>
                            <div class="bg-[#2a4a75] p-3 rounded border-l-4 border-red-500">
                                <p class="text-[9px] font-black uppercase opacity-80 mb-1">Prom. Vencidas</p>
                                <p id="rm-vencidas" class="text-2xl font-bold text-red-400">0</p>
                            </div>
                        </div>
                        <!-- Progress and Rank -->
                        <div class="bg-[#162e4d] p-5 rounded-lg border border-gray-700">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">🏆</span>
                                    <div>
                                        <p class="text-[10px] font-black uppercase opacity-80">Posición Actual</p>
                                        <p class="text-lg font-bold"><span id="rm-posicion">#0</span> <span class="text-xs font-medium opacity-60 ml-1" id="rm-total-ops">de 0</span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="w-full bg-gray-700 h-3 rounded-full overflow-hidden mb-2">
                                <div id="rm-barra" class="bg-cyan-400 h-full w-[0%] transition-all duration-1000"></div>
                            </div>
                            <p id="rm-distancia" class="text-xs font-medium">Distancia al Líder <span class="text-green-400 font-bold">-</span></p>
                        </div>
                    </section>

                    <!-- MI AGENDA HOY -->
                    <section class="bg-white rounded-xl custom-shadow overflow-hidden" data-purpose="agenda-section">
                        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                            <h2 class="text-xs font-extrabold text-gray-800 uppercase tracking-wider">Mi Agenda Hoy</h2>
                            <button onclick="cargarMiRendimiento()" class="text-gray-400 hover:text-crm-blue transition-colors">
                                <span class="material-symbols-outlined text-xl">sync</span>
                            </button>
                        </div>
                        <div id="rm-agenda-body" class="divide-y divide-gray-50 min-h-[100px]">
                            <!-- Agenda rows here -->
                            <p class="p-10 text-center text-[10px] font-black text-slate-400 uppercase">Cargando agenda...</p>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Podium Sidebar -->
                <aside class="w-full lg:w-72 space-y-4">
                    <h2 class="text-[10px] font-extrabold text-gray-700 mb-4 uppercase tracking-widest pl-1">Podio del Mes</h2>
                    <div id="rm-ranking-body" class="space-y-4">
                        <!-- Ranking items here -->
                        <p class="text-center py-10 text-[10px] font-black text-slate-400 uppercase">Cargando...</p>
                    </div>
                </aside>
            </div>
        </section>

        <section id="sec-reportes" class="hidden space-y-6">
            <!-- Controles de filtro -->
            <div class="bg-white p-6 md:p-8 rounded-xl border border-gray-100 shadow-sm">
                <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-6">Filtros de Análisis</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Desde</label>
                        <input type="date" id="rep-desde" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 outline-none">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Hasta</label>
                        <input type="date" id="rep-hasta" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 outline-none">
                    </div>
                    <div>
                        <label class="block text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Período Rápido</label>
                        <select id="rep-periodo" onchange="aplicarPeriodo()" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-700 outline-none cursor-pointer">
                            <option value="hoy">Hoy</option>
                            <option value="semana" selected>Últimos 7 días</option>
                            <option value="mes">Últimos 30 días</option>
                            <option value="trimestre">Últimos 90 días</option>
                            <option value="personalizado">Personalizado</option>
                        </select>
                    </div>
                    <button onclick="cargarResumenGeneral()" class="bg-crm-blue text-white px-8 py-3 rounded-xl text-[10px] font-black uppercase shadow-sm hover:opacity-90 transition">Actualizar</button>
                </div>
            </div>

            <!-- Tarjeta de Resumen General -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-crm-dark-blue p-6 rounded-xl text-white custom-shadow">
                    <p class="text-[9px] font-black text-blue-300 uppercase tracking-widest mb-2">Operadores Activos</p>
                    <h4 id="rep-op-activos" class="text-3xl font-bold">0</h4>
                    <p id="rep-op-totales" class="text-[9px] font-bold text-gray-400 mt-2 uppercase tracking-widest">de 0 totales</p>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-100 custom-shadow">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Gestiones Total</p>
                    <h4 id="rep-gestiones" class="text-3xl font-bold text-crm-blue">0</h4>
                </div>
                <div class="bg-white p-6 rounded-xl border border-gray-100 custom-shadow">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-2">Clientes Únicos</p>
                    <h4 id="rep-clientes" class="text-3xl font-bold text-gray-800">0</h4>
                </div>
                <div class="bg-emerald-500 p-6 rounded-xl text-white custom-shadow">
                    <p class="text-[9px] font-black text-emerald-100 uppercase tracking-widest mb-2">Al Día Logrados</p>
                    <h4 id="rep-al-dia" class="text-3xl font-bold">0</h4>
                </div>
            </div>

            <!-- Pestañas de reportes -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex gap-0 border-b border-gray-100 overflow-x-auto custom-scroll">
                    <button onclick="cambiarReporte('ranking')" id="rep-tab-ranking" class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b-2 border-transparent whitespace-nowrap transition hover:text-crm-blue">Ranking</button>
                    <button onclick="cambiarReporte('productividad')" id="rep-tab-productividad" class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b-2 border-transparent whitespace-nowrap hover:text-crm-blue transition">Productividad</button>
                    <button onclick="cambiarReporte('efectividad')" id="rep-tab-efectividad" class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b-2 border-transparent whitespace-nowrap hover:text-crm-blue transition">Efectividad</button>
                    <button onclick="cambiarReporte('matriz')" id="rep-tab-matriz" class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b-2 border-transparent whitespace-nowrap hover:text-crm-blue transition">Matriz</button>
                    <button onclick="cambiarReporte('clientes')" id="rep-tab-clientes" class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b-2 border-transparent whitespace-nowrap hover:text-crm-blue transition">Top Clientes</button>
                    <button onclick="cambiarReporte('diario')" id="rep-tab-diario" class="px-6 py-5 text-[10px] font-black uppercase tracking-widest text-gray-400 border-b-2 border-transparent whitespace-nowrap hover:text-crm-blue transition">Diario</button>
                </div>
                <div id="rep-contenido" class="p-6 md:p-8 min-h-[400px]">
                    <p class="text-center py-20 text-gray-400 font-bold text-[10px] uppercase tracking-widest">Seleccione un período para comenzar.</p>
                </div>
            </div>

            <!-- Botones de exportación -->
            <div class="flex gap-3">
                <button onclick="exportarReporte('csv')" class="flex-1 bg-white border border-gray-200 text-gray-600 px-8 py-3 rounded-xl text-[10px] font-black uppercase shadow-sm hover:bg-gray-50 transition">Exportar CSV</button>
                <button onclick="exportarReporte('json')" class="flex-1 bg-white border border-gray-200 text-gray-600 px-8 py-3 rounded-xl text-[10px] font-black uppercase shadow-sm hover:bg-gray-50 transition">Exportar JSON</button>
            </div>
        </section>
    </main>

    <!-- BANNER FLOTANTE DE COMUNICADOS -->
    <!-- BANNER FLOTANTE DE COMUNICADOS -->
    <div id="banner-comunicado" class="hidden fixed bottom-6 right-6 max-w-sm w-full bg-crm-dark-blue border border-gray-700 shadow-2xl rounded-xl p-6 z-[100] transform transition-all duration-500 translate-y-10 opacity-0">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl animate-bounce">📣</span>
                <div>
                    <h3 class="text-white font-extrabold uppercase tracking-widest text-[10px]">Nuevo Aviso</h3>
                    <p id="lbl-destinatario-banner" class="text-[9px] text-blue-300 uppercase tracking-widest font-black"></p>
                </div>
            </div>
            <button onclick="cerrarComunicado()" class="text-gray-400 hover:text-white transition bg-white/10 w-6 h-6 rounded flex items-center justify-center">✕</button>
        </div>
        <p id="txt-comunicado" class="text-gray-200 text-xs font-medium leading-relaxed whitespace-pre-wrap"></p>
        <?php if($can_assign): ?>
        <div class="mt-4 pt-3 border-t border-white/10 text-right">
            <button onclick="eliminarComunicado()" class="text-[9px] font-black text-red-300 hover:text-red-200 uppercase tracking-widest transition">Quitar aviso</button>
        </div>
        <?php endif; ?>
    </div>

    <?php if($can_assign): ?>
    <!-- MODAL PARA REDACTAR COMUNICADOS -->
    <div id="modalComunicado" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white p-8 md:p-10 rounded-xl w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('modalComunicado').classList.replace('flex', 'hidden')" class="absolute top-6 right-6 text-gray-400 hover:text-gray-800 text-xl font-bold">✕</button>
            <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-6 flex items-center gap-2">📣 Lanzar Aviso</h3>
            <form id="formComunicado" onsubmit="guardarComunicado(event)" class="space-y-4">
                <select name="usuario_destino_id" id="comunicadoDestino" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold uppercase text-[10px] text-gray-700 cursor-pointer">
                    <option value="0">🌎 PARA TODOS LOS USUARIOS</option>
                </select>
                <textarea name="mensaje" rows="4" placeholder="Escribe tu mensaje aquí..." required class="w-full p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium outline-none resize-none custom-scroll focus:border-crm-blue focus:bg-white transition-colors" placeholder="Escribe el mensaje aquí..."></textarea>
                <button type="submit" class="w-full bg-crm-blue text-white py-4 rounded-xl font-black uppercase text-[10px] tracking-widest mt-2 hover:opacity-90 transition shadow-sm">Publicar Aviso</button>
            </form>
        </div>
    </div>
    
    <div id="bulk-actions" class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-crm-dark-blue p-4 rounded-xl shadow-2xl flex items-center gap-4 z-50 border border-gray-700 w-[95%] max-w-4xl overflow-x-auto custom-scroll">
        <span id="bulk-count" class="text-blue-300 font-extrabold text-[10px] px-4 whitespace-nowrap uppercase tracking-widest">0 seleccionados</span>
        <div class="w-px h-6 bg-gray-700 shrink-0"></div>
        <select id="masivo_operador" class="bg-white/10 text-white border border-white/20 text-[10px] px-4 py-2 rounded-lg outline-none font-bold uppercase">
            <option value="" class="text-gray-900">👤 Asignar a...</option>
            <option value="0" class="text-gray-900">Desasignar a todos</option>
        </select>
        <button onclick="ejecutarMasivo('asignar_operador')" class="bg-crm-blue text-white px-5 py-2 rounded-lg text-[10px] font-black uppercase hover:opacity-90 transition shrink-0">Aplicar</button>
        <div class="w-px h-6 bg-gray-700 shrink-0"></div>
        <select id="masivo_estado" class="bg-white/10 text-white border border-white/20 text-[10px] px-4 py-2 rounded-lg outline-none font-bold uppercase">
            <option value="" class="text-gray-900">Cambiar Estado...</option>
            <option value="al_dia" class="text-gray-900">Al Día</option>
            <option value="promesa" class="text-gray-900">Promesa de Pago</option>
            <option value="no_responde" class="text-gray-900">No Responde</option>
            <option value="no_corresponde" class="text-gray-900">No Corresponde</option>
            <option value="llamar" class="text-gray-900">Llamar luego</option>
            <option value="numero_baja" class="text-gray-900">Nro de Baja</option>
            <option value="carta" class="text-gray-900">Carta</option>
            <option value="otro" class="text-gray-900">Otro</option>
        </select>
        <button onclick="ejecutarMasivo('cambiar_estado')" class="bg-crm-blue text-white px-5 py-2 rounded-lg text-[10px] font-black uppercase hover:opacity-90 transition shrink-0">Aplicar</button>
        <?php if($rol_usuario === 'admin'): ?>
        <div class="w-px h-8 bg-slate-700 shrink-0"></div>
        <button onclick="ejecutarMasivo('eliminar')" class="bg-rose-500/10 text-rose-500 border border-rose-500/20 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-rose-500 hover:text-white transition shrink-0">🗑️ Eliminar</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="modal" class="fixed inset-0 bg-crm-dark-blue/80 backdrop-blur-md hidden items-center justify-center p-0 md:p-4 z-50" onclick="closeModal()">
        <!-- Contenedor principal: fullscreen en mobile, card en desktop -->
        <div class="bg-white w-full h-full md:rounded-xl md:max-w-7xl md:h-[90vh] shadow-2xl overflow-hidden flex flex-col md:scale-100 md:opacity-100 transition-all duration-300" id="mContent" onclick="event.stopPropagation()">

            <!-- CABECERA PERSISTENTE -->
            <div class="px-5 pt-6 pb-4 md:px-10 md:pt-8 border-b border-gray-100 shrink-0 bg-white">
                <div class="flex justify-between items-start gap-2 mb-4">
                    <div class="flex-1 min-w-0">
                        <h3 id="mRazon" class="text-xl md:text-2xl font-extrabold text-gray-800 uppercase tracking-tight leading-tight truncate"></h3>
                        <div class="flex flex-wrap items-center gap-3 mt-1">
                            <p id="mLegajo" class="text-crm-blue font-black text-[10px] uppercase tracking-widest"></p>
                            <span id="mWarningOtroOp" class="hidden bg-amber-50 text-amber-600 text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded border border-amber-100"></span>
                            <span id="mSucursal" class="bg-gray-100 px-2 py-0.5 rounded text-[8px] font-extrabold uppercase text-gray-400 italic hidden md:inline"></span>
                        </div>
                    </div>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-800 w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center text-sm shrink-0">✕</button>
                </div>

                <div class="flex items-center justify-between gap-2 md:hidden mb-4">
                    <span id="mSucursalMobile" class="bg-gray-100 px-3 py-1.5 rounded-lg text-[8px] font-black uppercase text-gray-400"></span>
                    <?php if($can_assign): ?>
                    <select id="mAsignacion" onchange="cambiarAsignacion(this.value)" class="bg-blue-50 text-crm-blue px-3 py-1.5 rounded-lg text-[9px] font-black uppercase outline-none border border-blue-100 appearance-none cursor-pointer max-w-[160px]">
                        <option value="">👤 Sin Asignar</option>
                    </select>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-4 gap-3">
                    <div class="bg-gray-50 p-2 md:p-4 rounded-xl border border-gray-100"><p class="text-[8px] font-black text-gray-400 uppercase mb-0.5">Vencido</p><h5 id="mTotal" class="text-sm md:text-base font-bold text-gray-800"></h5></div>
                    <div class="bg-red-50 p-2 md:p-4 rounded-xl border border-red-100"><p class="text-[8px] font-black text-red-400 uppercase mb-0.5">Atraso</p><h5 id="mDias" class="text-sm md:text-base font-bold text-red-600"></h5></div>
                    <div class="bg-gray-50 p-2 md:p-4 rounded-xl border border-gray-100"><p class="text-[8px] font-black text-gray-400 uppercase mb-0.5">Cuotas</p><h5 id="mCuotas" class="text-sm md:text-base font-bold text-gray-800"></h5></div>
                    <div class="bg-gray-50 p-2 md:p-4 rounded-xl border border-gray-100"><p class="text-[8px] font-black text-gray-400 uppercase mb-0.5">Vto</p><h5 id="mVenc" class="text-sm md:text-base font-bold text-gray-800"></h5></div>
                </div>

                <div class="flex flex-col gap-2 mt-4 text-[10px] font-bold text-gray-400 bg-gray-50 px-5 py-3 rounded-xl border border-gray-100">
                    <!-- Fila 1: Dirección, Documento, Último Pago -->
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span class="flex items-center gap-1.5 min-w-0">
                            <span class="text-xs shrink-0">📍</span>
                            <span id="mDomicilio" class="text-gray-600 truncate"></span>
                        </span>
                        <span class="w-px h-3 bg-gray-300 shrink-0 hidden sm:inline-block"></span>
                        <span class="font-extrabold uppercase text-[8px] tracking-widest shrink-0">Doc: <span id="mDocumento" class="text-gray-600 ml-0.5"></span></span>
                        <span class="w-px h-3 bg-gray-300 shrink-0 hidden sm:inline-block"></span>
                        <span class="font-extrabold uppercase text-[8px] tracking-widest shrink-0">Último Pago: <span id="mUltimo" class="text-gray-600 ml-0.5"></span></span>
                    </div>
                    <!-- Fila 2: Teléfonos y botones de contacto -->
                    <div id="mWALinks" class="flex flex-wrap items-center gap-2"></div>
                </div>

                <!-- TAB SWITCHER (solo mobile) -->
                <div class="md:hidden flex gap-1 mt-4 bg-gray-100 rounded-xl p-1">
                    <button id="tabGestion" onclick="switchModalTab('gestion')" class="flex-1 py-2.5 rounded-lg text-[9px] font-black uppercase tracking-widest bg-white text-crm-blue shadow-sm transition">✏️ Gestión</button>
                    <button id="tabHistorial" onclick="switchModalTab('historial')" class="flex-1 py-2.5 rounded-lg text-[9px] font-black uppercase tracking-widest text-gray-400 transition">📋 Historial</button>
                </div>
            </div>

            <div class="flex-1 flex overflow-hidden flex-col md:flex-row">
                <!-- PANEL IZQUIERDO: GESTIÓN -->
                <div id="panelGestion" class="w-full md:w-3/5 flex flex-col bg-white overflow-y-auto px-5 pt-6 pb-6 md:px-10 md:pt-8 border-r border-gray-100 custom-scroll">
                    <?php if($can_assign): ?>
                    <div class="hidden md:flex justify-end mb-6">
                        <select id="mAsignacionDesktop" onchange="cambiarAsignacion(this.value)" class="bg-gray-50 text-crm-blue px-4 py-2 rounded-lg text-[9px] font-black uppercase outline-none border border-gray-100 text-right appearance-none cursor-pointer hover:bg-white transition">
                            <option value="">👤 Sin Asignar</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <form id="gForm" class="space-y-4 flex flex-col">
                        <input type="hidden" name="action" id="gAction" value="insert">
                        <input type="hidden" name="id" id="gId" value="">
                        <input type="hidden" name="legajo" id="mLegajoRaw">

                        <div class="flex items-center gap-2 mb-2 hidden" id="editWarning">
                            <span class="bg-amber-50 text-amber-600 text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded border border-amber-100">Modo Edición</span>
                            <button type="button" onclick="cancelarEdicion()" class="text-red-500 text-[9px] font-black uppercase hover:underline">Cancelar</button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <select name="estado" id="mEst" class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:ring-4 focus:ring-crm-blue/10 transition">
                                <option value="promesa">Promesa de Pago</option>
                                <option value="al_dia" <?= $rol_usuario === 'operador' ? 'disabled class="hidden"' : '' ?>>Al Día (Sin deuda)</option>
                                <option value="no_responde">No Responde</option>
                                <option value="no_corresponde">No Corresponde</option>
                                <option value="llamar">Llamar más tarde</option>
                                <option value="numero_baja">Número dado de baja</option>
                                <option value="carta">Carta</option>
                                <option value="otro">Otro</option>
                            </select>
                            <input type="number" step="0.01" name="monto_promesa" id="mMon" placeholder="Monto Acuerdo" class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:ring-4 focus:ring-crm-blue/10 transition">
                            <input type="date" name="fecha_promesa" id="mFec" class="p-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:ring-4 focus:ring-crm-blue/10 transition" required="true" min="<?= date('Y-m-d') ?>">
                        </div>

                        <div id="tips-estado" class="hidden bg-blue-50 border border-blue-100 p-4 rounded-xl text-xs text-blue-800 transition-all duration-300">
                             <div class="flex gap-3"><span class="text-lg">💡</span><div><h5 class="font-black uppercase tracking-widest text-[8px] mb-1">Tip de Gestión</h5><p id="tips-texto" class="text-[11px] font-medium"></p></div></div>
                        </div>

                        <textarea name="observacion" rows="4" required class="w-full p-5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium outline-none resize-none custom-scroll focus:bg-white focus:ring-4 focus:ring-crm-blue/10 transition" placeholder="Resumen de la conversación..."></textarea>
                        <!-- Guardar + Navegación en una sola fila -->
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnNavPrev" onclick="navegarCliente(-1)" title="Cliente anterior"
                                class="flex-1 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-500 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition disabled:opacity-30 disabled:cursor-not-allowed">
                                ← Ant
                            </button>
                            <button type="submit" id="btnSubmitGestion" class="flex-[2] bg-crm-blue text-white py-4 rounded-xl font-black uppercase text-[10px] tracking-widest shadow-sm hover:opacity-90 transition">
                                Guardar Gestión
                            </button>
                            <button type="button" id="btnNavNext" onclick="navegarCliente(1)" title="Siguiente cliente"
                                class="flex-1 flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-500 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest transition disabled:opacity-30 disabled:cursor-not-allowed">
                                Sig →
                            </button>
                        </div>
                        <p class="text-center text-[9px] font-black text-gray-300 uppercase tracking-widest -mt-1"><span id="navIndicator">— / —</span></p>
                    </form>
                </div>

                <!-- PANEL DERECHO: HISTORIAL -->
                <div id="panelHistorial" class="hidden md:flex w-full md:w-2/5 flex-col bg-gray-50 overflow-hidden">
                    <div class="px-6 py-5 md:px-10 md:py-8 flex justify-between items-center border-b border-gray-200 shrink-0 hidden md:flex bg-white">
                        <h4 class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Línea de Tiempo</h4>
                    </div>
                    <div id="mHis" class="flex-1 overflow-y-auto space-y-4 custom-scroll p-6 md:p-8"></div>
                </div>
            </div>
        </div>
    </div>

        </div>
    </div>
    
    <?php if($rol_usuario === 'admin'): ?>
    <div id="modalUser" class="fixed inset-0 bg-crm-dark-blue/80 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white p-8 md:p-10 rounded-xl w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('modalUser').classList.replace('flex', 'hidden')" class="absolute top-6 right-6 text-gray-400 hover:text-gray-800 text-xl font-bold">✕</button>
            <h3 class="text-xs font-extrabold text-gray-400 uppercase tracking-widest mb-6 italic">Personal Operativo</h3>
            <form id="userForm" class="space-y-4">
                <input type="hidden" name="id" id="uId">
                <input type="text" name="nombre" id="uNom" placeholder="Nombre completo" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-xs">
                <input type="email" name="usuario" id="uMail" placeholder="Email (Usuario de login)" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-xs">
                <input type="password" name="clave" id="uPass" placeholder="Contraseña (dejar vacío si no cambia)" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-xs">
                <select name="rol" id="uRol" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl outline-none font-bold text-xs text-gray-600">
                    <option value="operador">Operador Base</option>
                    <option value="colaborador">Colaborador (Coordinador)</option>
                    <option value="admin">Administrador Total</option>
                </select>
                <button type="submit" class="w-full bg-crm-blue text-white py-4 rounded-xl font-black uppercase text-[10px] tracking-widest mt-2 hover:opacity-90 transition shadow-sm">Guardar Personal</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

<script>
    const currentUserId = <?= (int)$user_id ?>;
    const isAdmin = <?= $rol_usuario === 'admin' ? 'true' : 'false' ?>;
    const isColab = <?= $rol_usuario === 'colaborador' ? 'true' : 'false' ?>;
    const canAssign = isAdmin || isColab;
    const isOperador = <?= $rol_usuario === 'operador' ? 'true' : 'false' ?>;
    
    const cfgEstados = [
        { id: 'sin_gestion', label: 'PENDIENTE', class: 'badge-sin_gestion' },
        { id: 'promesa', label: 'PROMESA', class: 'badge-promesa' },
        { id: 'al_dia', label: 'AL DÍA', class: 'badge-al_dia' },
        { id: 'llamar', label: 'LLAMAR', class: 'badge-llamar' },
        { id: 'no_responde', label: 'NO RESPONDE', class: 'badge-no_responde' },
        { id: 'no_corresponde', label: 'NO CORRESPONDE', class: 'badge-no_corresponde' },
        { id: 'numero_baja', label: 'NRO BAJA', class: 'badge-numero_baja' },
        { id: 'carta', label: 'CARTA', class: 'badge-carta' },
        { id: 'otro', label: 'OTRO', class: 'badge-otro' }
    ];

    /**
     * Lógica del Drawer Móvil
     * Alterna la visibilidad del menú lateral (Drawer)
     */
    function toggleMenu() {
        const menu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('mobileMenuOverlay');
        const drawer = document.getElementById('mobileMenuDrawer');
        
        if (menu.classList.contains('hidden')) {
            // Mostrar menú
            menu.classList.remove('hidden');
            // Pequeño delay para permitir que el display:block se aplique antes de animar
            setTimeout(() => {
                overlay.classList.remove('opacity-0');
                drawer.classList.remove('translate-x-full');
            }, 10);
        } else {
            // Ocultar menú
            overlay.classList.add('opacity-0');
            drawer.classList.add('translate-x-full');
            // Esperar a que termine la animación antes de aplicar display:none
            setTimeout(() => {
                menu.classList.add('hidden');
            }, 300);
        }
    }

    let operadoresList = [];
    let selectedLegajos = []; 
    let notificacionesData = [];

    const api_clientes = 'api_clientes.php?action=';
    const api_gestion = 'api_gestion.php';
    const api_historial = 'api_historial.php?legajo=';
    const api_usuarios = 'api_usuarios.php';
    const api_dashboard = 'api_dashboard.php';

    // ── COMUNICADOS ──
    async function checkComunicado() {
        try {
            // Se agregó un timestamp (_t) para romper la caché estricta de Hostinger y que el Short Polling funcione
            const res = await fetch(`api_comunicados.php?action=get&_t=${Date.now()}`);
            const d = await res.json();
            if (d.success && d.data) {
                const lastSeen = localStorage.getItem('comunicado_visto');
                if (lastSeen != d.data.id) {
                    document.getElementById('txt-comunicado').innerText = d.data.mensaje;
                    const lblDestino = document.getElementById('lbl-destinatario-banner');
                    
                    if (!d.data.usuario_destino_id || d.data.usuario_destino_id == 0) {
                        lblDestino.innerText = "🌎 PARA TODOS";
                        lblDestino.classList.replace('text-rose-400', 'text-blue-400');
                    } else {
                        lblDestino.innerText = "🔒 MENSAJE PRIVADO PARA TI";
                        lblDestino.classList.replace('text-blue-400', 'text-rose-400');
                    }

                    const banner = document.getElementById('banner-comunicado');
                    banner.dataset.id = d.data.id;
                    banner.classList.remove('hidden');
                    setTimeout(() => { banner.classList.remove('translate-y-10', 'opacity-0'); }, 100);
                }
            }
        } catch(e) { console.log('Error silenciado de comunicados'); }
    }

    function cerrarComunicado() {
        const banner = document.getElementById('banner-comunicado');
        banner.classList.add('translate-y-10', 'opacity-0');
        localStorage.setItem('comunicado_visto', banner.dataset.id);
        setTimeout(() => banner.classList.add('hidden'), 500);
    }

    <?php if($can_assign): ?>
    function abrirModalComunicado() { 
        document.getElementById('formComunicado').reset(); 
        const selectDestino = document.getElementById('comunicadoDestino');
        selectDestino.innerHTML = '<option value="0">🌎 PARA TODOS LOS USUARIOS</option>' + 
            operadoresList.map(u => `<option value="${u.id}">👤 Para: ${u.nombre} (${u.rol})</option>`).join('');
        document.getElementById('modalComunicado').classList.replace('hidden', 'flex'); 
    }
    
    async function guardarComunicado(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'save');
        const res = await fetch('api_comunicados.php', { method: 'POST', body: fd });
        const d = await res.json();
        if (d.success) {
            document.getElementById('modalComunicado').classList.replace('flex', 'hidden');
            localStorage.removeItem('comunicado_visto');
            checkComunicado();
        } else { alert("Error al guardar aviso: " + d.message); }
    }

    async function eliminarComunicado() {
        if (!confirm('¿Estás seguro de quitar este aviso?')) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        await fetch('api_comunicados.php', { method: 'POST', body: fd });
        cerrarComunicado();
    }
    <?php endif; ?>

    function aplicarFiltroDashboard(estadoId) {
        document.getElementById('filter-estado').value = estadoId;
        document.getElementById('search').value = '';
        if(document.getElementById('filter-operador')) document.getElementById('filter-operador').value = '0';
        switchTab('clientes');
        load(); stats();
    }

    function toggleNotificaciones(e) { e.stopPropagation(); document.getElementById('noti-dropdown').classList.toggle('hidden'); }
    function cerrarDropdownNoti() { const d = document.getElementById('noti-dropdown'); if (d && !d.classList.contains('hidden')) d.classList.add('hidden'); }

    const loadNotificaciones = async () => {
        try {
            const res = await fetch(api_clientes + 'notificaciones');
            const d = await res.json();
            const badge = document.getElementById('noti-badge');
            const countText = document.getElementById('noti-count-text');
            const list = document.getElementById('noti-list');
            notificacionesData = d.data;
            if (d.count > 0) {
                badge.innerText = d.count;
                badge.classList.remove('hidden');
                countText.innerText = d.count + (d.count === 1 ? ' ALERTA' : ' ALERTAS');
                list.innerHTML = d.data.map((c, i) => {
                    let f = c.fecha_promesa.split('-').reverse().join('/');
                    let estado = c.estado_actual.replace('_', ' ').toUpperCase();
                    let badgeClass = `badge-${c.estado_actual}`;
                    return `<div class="p-4 hover:bg-slate-50 cursor-pointer transition flex flex-col gap-2" onclick='abrirNotificacion(event, ${i})'>
                        <div class="flex justify-between items-start">
                            <p class="font-black text-xs text-slate-800 uppercase truncate pr-2">${c.razon_social}</p>
                            <span class="px-2 py-0.5 rounded text-[8px] font-black whitespace-nowrap ${badgeClass}">${estado}</span>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <p class="text-[10px] font-bold text-slate-500">Leg: ${c.legajo}</p>
                            <p class="text-[10px] font-black text-rose-600 bg-rose-50 px-2 py-1 rounded-md">📅 Vencido: ${f}</p>
                        </div>
                    </div>`;
                }).join('');
            } else {
                badge.classList.add('hidden');
                countText.innerText = '0 ALERTAS';
                list.innerHTML = '<p class="text-center py-8 text-[10px] font-black uppercase text-slate-300 tracking-widest">Agenda al día 🎉</p>';
            }
        } catch(e) {}
    };

    function abrirNotificacion(e, index) { e.stopPropagation(); cerrarDropdownNoti(); abrirClientePorLegajo(notificacionesData[index].legajo); }

    async function abrirClientePorLegajo(legajo) {
        try {
            // Buscamos la ficha completa del cliente antes de abrir el modal
            const res = await fetch(`${api_clientes}search&q=${encodeURIComponent(legajo)}&estado=&operador_id=0`);
            const data = await res.json();
            const cliente = data.find(c => c.legajo === legajo);
            
            if (cliente) {
                openModal(cliente);
            } else {
                alert('No se pudo cargar la ficha completa de este cliente.');
            }
        } catch (e) {
            console.error('Error al obtener datos del cliente:', e);
            alert('Error al abrir la ficha del cliente.');
        }
    }

    function exportarExcel() { 
        const q = document.getElementById('search').value;
        const est = document.getElementById('filter-estado').value;
        const op = document.getElementById('filter-operador')?.value || 0;
        const isMoto = document.getElementById('filtroMoto')?.checked ? 1 : 0; // NUEVO FILTRO MOTO EXCEL
        let url = `exportar_csv.php?q=${encodeURIComponent(q)}&estado=${encodeURIComponent(est)}&operador_id=${encodeURIComponent(op)}&moto=${isMoto}`;
        window.location.href = url;
    }

    const loadDashboard = async () => {
        if (!canAssign) return;
        try {
            document.getElementById('listaDashboard').innerHTML = `<tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold text-xs uppercase tracking-widest">Cargando métricas...</td></tr>`;
            document.getElementById('listaSucursales').innerHTML = `<tr><td colspan="4" class="text-center py-6 text-slate-400 font-bold text-xs uppercase tracking-widest">Cargando...</td></tr>`;
            document.getElementById('feedGestiones').innerHTML = `<p class="text-center text-slate-500 text-xs font-bold mt-10 uppercase tracking-widest">Cargando feed en vivo...</p>`;
            
            const res = await fetch(api_dashboard);
            const data = await res.json();
            
            if (data.success) {
                if (document.getElementById('dash-asignados')) {
                    document.getElementById('dash-asignados').innerText = data.resumen.total_asignados || '0';
                    document.getElementById('dash-gestionados').innerText = data.resumen.total_gestionados || '0';
                    document.getElementById('dash-promesas-totales').innerText = data.resumen.total_promesas || '0';
                    document.getElementById('dash-cobertura').innerText = (data.resumen.cobertura || '0') + '%';
                }

                if (document.getElementById('dash-estados') && data.estados) {
                    document.getElementById('dash-estados').innerHTML = cfgEstados.map(est => {
                        const count = data.estados[est.id] || 0;
                        return `<div onclick="aplicarFiltroDashboard('${est.id}')" class="cursor-pointer bg-white p-3 rounded-2xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center hover:shadow-lg hover:border-blue-400 transition transform hover:-translate-y-1">
                            <h4 class="text-xl font-black text-slate-800 mb-2">${count}</h4>
                            <span class="px-2 py-1 rounded-full text-[8px] font-black uppercase ${est.class} w-full truncate">${est.label}</span>
                        </div>`;
                    }).join('');
                }

                if (document.getElementById('listaSucursales') && data.sucursales) {
                    document.getElementById('listaSucursales').innerHTML = data.sucursales.map(s => {
                        let monto = `$${parseFloat(s.deuda_en_calle || 0).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                        let gestiones = s.total_gestiones || 0;
                        let pctGestion = s.total_clientes > 0 ? Math.round((gestiones / s.total_clientes) * 100) : 0;
                        return `<tr class="hover:bg-blue-50/50 transition border-b border-slate-50">
                            <td class="px-6 py-4 font-black text-slate-800 uppercase text-[11px] tracking-widest">${s.sucursal_nombre}</td>
                            <td class="px-6 py-4 text-center font-bold text-slate-500">${s.total_clientes}</td>
                            <td class="px-6 py-4 text-center font-bold text-blue-600">${gestiones}</td>
                            <td class="px-6 py-4 text-center font-black text-slate-700">${pctGestion}%</td>
                            <td class="px-6 py-4 text-right font-black text-rose-600 bg-rose-50/30">${monto}</td>
                        </tr>`;
                    }).join('') || `<tr><td colspan="4" class="text-center py-6 text-slate-400 font-bold text-xs uppercase tracking-widest">Sin datos</td></tr>`;
                }

                if (document.getElementById('listaDashboard') && data.data) {
                    document.getElementById('listaDashboard').innerHTML = data.data.map(op => {
                        const asignados = parseInt(op.total_asignados) || 0;
                        const gestionados = parseInt(op.clientes_gestionados) || 0; 
                        const total_gestiones = parseInt(op.total_gestiones) || 0;
                        const promesas = parseInt(op.promesas_logradas) || 0;
                        
                        const pctGestionados = asignados > 0 ? Math.round((gestionados / asignados) * 100) : 0;
                        const efectividad = total_gestiones > 0 ? Math.round((promesas / total_gestiones) * 100) : 0;
                        const colorEfectividad = efectividad >= 30 ? 'text-emerald-500' : (efectividad >= 15 ? 'text-amber-500' : 'text-rose-500');
                        
                        return `<tr class="hover:bg-blue-50/50 transition border-b border-slate-50">
                            <td class="px-6 py-4 font-black text-slate-800 text-xs">${op.nombre || 'Desconocido'}</td>
                            <td class="px-6 py-4 text-center font-bold text-slate-600">${asignados}</td>
                            <td class="px-6 py-4 text-center font-bold text-blue-600">${total_gestiones}</td>
                            <td class="px-6 py-4 text-center font-black text-slate-700">${pctGestionados}%</td>
                            <td class="px-6 py-4 text-center font-bold text-emerald-600">${promesas}</td>
                            <td class="px-6 py-4 text-center font-black ${colorEfectividad}">${efectividad}%</td>
                        </tr>`;
                    }).join('') || `<tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold text-xs uppercase tracking-widest">No hay datos</td></tr>`;
                }

                if (document.getElementById('feedGestiones') && data.ultimas_gestiones) {
                document.getElementById('feedGestiones').innerHTML = data.ultimas_gestiones.map(g => {
                    let estadoStr = g.feed_estado || 'sin_gestion';
                    let badge = `badge-${estadoStr}`;
                    let fechaStr = g.feed_fecha || '';
                    let fechaHora = fechaStr.length >= 16 ? fechaStr.substring(0, 16).replace(/-/g, '/') : fechaStr;
                    
                    return `<div onclick="abrirClientePorLegajo('${g.legajo}')" class="p-4 bg-slate-50 rounded-2xl border border-slate-200 hover:bg-blue-50 hover:border-blue-200 cursor-pointer transition mb-3 group">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">${fechaHora}</span>
                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase ${badge}">${estadoStr.replace('_', ' ')}</span>
                        </div>
                        <p class="font-bold text-[13px] text-slate-800 mb-1 truncate group-hover:text-blue-600 transition-colors">${g.razon_social || 'Desconocido'}</p>
                        <p class="text-[11px] text-slate-500 line-clamp-2 leading-relaxed font-medium italic">"${g.feed_obs || 'Sin observaciones'}"</p>
                        <div class="mt-2 pt-2 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">LEG: ${g.legajo}</span>
                            <span class="text-[9px] text-blue-500 font-bold uppercase">👤 ${(g.feed_operador || 'Sistema').split(' ')[0]}</span>
                        </div>
                    </div>`;
                }).join('') || `<p class="text-center text-slate-500 text-xs font-bold mt-10 uppercase tracking-widest">No hay gestiones</p>`;
            }
        } else {
             throw new Error(data.message || "El backend devolvió un error desconocido");
        }
    } catch (e) { 
        console.error(e);
        let errMsg = e.message || "Error de conexión";
        if(document.getElementById('listaDashboard')) document.getElementById('listaDashboard').innerHTML = `<tr><td colspan="5" class="text-center py-10 text-rose-400 font-bold text-xs uppercase tracking-widest">⚠️ ERROR AL CARGAR DATOS<br><span class="text-[9px] text-slate-500 lowercase normal-case mt-2 block">${errMsg}</span></td></tr>`; 
        if(document.getElementById('feedGestiones')) document.getElementById('feedGestiones').innerHTML = `<div class="p-4 bg-rose-50 border border-rose-100 rounded-2xl mt-10"><p class="text-center text-rose-500 text-xs font-bold uppercase tracking-widest">⚠️ Error en el Feed</p><p class="text-center text-slate-600 text-[10px] mt-2">${errMsg}</p></div>`;
    }
};

const load = async () => {
        const q = document.getElementById('search').value;
        const est = document.getElementById('filter-estado').value;
        const op = document.getElementById('filter-operador')?.value || 0;
        const limit = Math.min(parseInt(document.getElementById('filter-limit')?.value || 200), 500);
        const isMoto = document.getElementById('filtroMoto')?.checked ? 1 : 0; // NUEVO FILTRO MOTO FETCH
        let url = `${api_clientes}search&q=${encodeURIComponent(q)}&estado=${est}&operador_id=${op}&limit=${limit}&moto=${isMoto}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            window.listaClientes = data; // Guardar lista para navegación en modal
            
            document.getElementById('lista').innerHTML = data.map(c => {
                let badgeClass = `badge-${c.estado_actual}`;
                let labelEstado = c.estado_actual === 'sin_gestion' ? 'PENDIENTE' : c.estado_actual.replace('_', ' ').toUpperCase();
                
                // NUEVO BOTÓN MOTO ROJO (se muestra si la base de datos devuelve moto=1)
                let badgeMoto = c.moto == 1 ? `<span class="inline-flex items-center px-1.5 py-0.5 ml-2 text-[9px] font-black uppercase tracking-widest text-white bg-red-600 rounded-full animate-pulse border border-red-800 shadow-sm" title="Deuda Moto">🚨 MOTO</span>` : '';

                let checkHTML = canAssign ? `<td class="pl-8 pr-2 py-5 text-left" onclick="event.stopPropagation()"><input type="checkbox" value="${c.legajo}" onchange="toggleSelection(event)" ${selectedLegajos.includes(c.legajo) ? 'checked' : ''} class="chk-legajo w-4 h-4 rounded text-blue-600 bg-slate-100 border-slate-300"></td>` : '';
                let paddingLegajo = canAssign ? 'px-2' : 'px-8';

                let opBadge = canAssign ? 
                    `<select onchange="cambiarAsignacionRapida('${c.legajo}', this.value)" onclick="event.stopPropagation()" class="mt-2 w-full max-w-[120px] bg-white border border-slate-200 rounded-lg text-[9px] font-black uppercase outline-none py-1 px-1">
                        <option value="">👤 Sin Asignar</option>${operadoresList.map(o => `<option value="${o.id}" ${c.operador_id == o.id ? 'selected' : ''}>👤 ${o.nombre.split(' ')[0]}</option>`).join('')}
                    </select>` : 
                    `<p class="mt-2 text-[9px] font-black text-blue-500 uppercase tracking-widest">👤 ${c.operador_asignado?.split(' ')[0] || 'SIN ASIGNAR'}</p>`;

                let trMonto = `$${parseFloat(c.total_vencido).toLocaleString('es-AR')}`;

                let cJson = JSON.stringify(c).replace(/'/g, "\\'").replace(/"/g, '&quot;'); 

                // ETIQUETA DE COINCIDENCIA (Nueva solicitud)
                let badgeMatch = c.match_reason ? `<span class="inline-flex items-center px-1.5 py-0.5 ml-1 text-[8px] font-black uppercase tracking-widest text-slate-500 bg-slate-100 border border-slate-200 rounded shadow-sm" title="Coincidencia en ${c.match_reason}">🔍 ${c.match_reason}</span>` : '';

                return `<tr class="hover:bg-blue-50/50 cursor-pointer transition border-l-[6px] border-l-${c.semaforo === 'blanco' ? 'transparent' : (c.semaforo === 'rojo' ? 'rose-500' : (c.semaforo === 'amarillo' ? 'amber-400' : 'emerald-500'))}" onclick="openModal(${cJson})">
                    ${checkHTML}
                    <td class="${paddingLegajo} py-3 md:py-4 whitespace-nowrap">
                        <div class="flex flex-col">
                            <p class="font-bold text-slate-800 text-xs flex items-center gap-1">${c.legajo} ${badgeMoto} ${badgeMatch}</p>
                            <p class="text-[8px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">${c.nro_documento}</p>
                        </div>
                    </td>
                    <td class="px-2 md:px-6 py-3 md:py-4">
                        <div class="flex flex-col">
                            <p class="font-bold uppercase text-slate-800 text-[11px] truncate max-w-[140px] md:max-w-[200px] lg:max-w-[240px]" title="${c.razon_social}">${c.razon_social}</p>
                            <div class="flex flex-wrap items-center gap-2 mt-1">
                                <p class="text-[8px] font-bold text-blue-500 uppercase italic whitespace-nowrap">${c.sucursal || 'Central'}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 md:py-4 text-center whitespace-nowrap">
                        <p class="font-bold text-slate-800 text-xs">${c.c_cuotas} Ctas</p>
                        ${c.dias_atraso > 0 ? `<p class="text-[9px] font-black text-rose-500 uppercase">${c.dias_atraso} días</p>` : ''}
                    </td>
                    <td class="px-6 py-3 md:py-4 text-right whitespace-nowrap"><p class="font-black text-slate-900 text-sm">${trMonto}</p></td>
                    <td class="px-6 py-3 md:py-4 text-center whitespace-nowrap"><span class="px-3 py-1 rounded-full text-[9px] font-black uppercase ${badgeClass}">${labelEstado}</span><div class="flex justify-center">${opBadge}</div></td>
                    <td class="pr-4 md:pr-6 pl-1 py-3 md:py-4 text-center whitespace-nowrap"><button onclick="event.stopPropagation(); openModal(${cJson})" class="bg-blue-600 text-white px-3 md:px-4 py-2 rounded-lg text-[9px] font-black uppercase hover:bg-blue-700 transition shadow-sm">Gestionar</button></td>
                </tr>`;
            }).join('');
            updateBulkUI();
        } catch(e) { console.error(e); }
    };

    const loadPersonal = async () => {
        const res = await fetch(api_usuarios+'?action=list');
        const data = await res.json();
        document.getElementById('listaPersonal').innerHTML = data.map(u => {
            let roleColor = u.rol === 'admin' ? 'text-rose-500' : (u.rol === 'colaborador' ? 'text-blue-500' : 'text-orange-500');
            let roleLabel = u.rol === 'admin' ? 'Administrador' : (u.rol === 'colaborador' ? 'Colaborador' : 'Operador');
            
            let uJson = JSON.stringify(u).replace(/'/g, "\\'").replace(/"/g, '&quot;'); 
            let btns = isAdmin ? `<div class="absolute top-6 right-6 flex gap-2"><button onclick="editarUsuario(${uJson})" class="p-2 bg-blue-50 text-blue-600 rounded-xl transition hover:bg-blue-600 hover:text-white">✏️</button>${u.id != currentUserId ? `<button onclick='eliminarUsuario(${u.id}, "${u.nombre}")' class="p-2 bg-rose-50 text-rose-600 rounded-xl transition hover:bg-rose-600 hover:text-white">🗑️</button>` : ''}</div>` : '';
            
            return `
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col justify-between group relative">
                ${btns}
                <div class="mt-4">
                    <p class="font-extrabold text-slate-800 text-lg uppercase tracking-tighter mb-1 pr-16">${u.nombre}</p>
                    <p class="text-[9px] font-black text-slate-400 uppercase break-all mb-2">${u.usuario}</p>
                    <p class="text-[10px] font-black uppercase tracking-widest ${roleColor}">${roleLabel}</p>
                </div>
            </div>`;
        }).join('');
    };

    function toggleSelectAll(e) { const checked = e.target.checked; document.querySelectorAll('.chk-legajo').forEach(chk => { chk.checked = checked; handleSelection(chk.value, checked); }); }
    function toggleSelection(e) { handleSelection(e.target.value, e.target.checked); }
    function handleSelection(legajo, isChecked) {
        if(isChecked && !selectedLegajos.includes(legajo)) selectedLegajos.push(legajo);
        else if(!isChecked) selectedLegajos = selectedLegajos.filter(l => l !== legajo);
        updateBulkUI();
    }
    function updateBulkUI() {
        const bar = document.getElementById('bulk-actions');
        if(!bar) return;
        if(selectedLegajos.length > 0) { bar.classList.remove('hidden'); document.getElementById('bulk-count').innerText = selectedLegajos.length + ' seleccionados'; } 
        else { bar.classList.add('hidden'); }
    }

    async function ejecutarMasivo(accion) {
        let fd = new FormData();
        fd.append('accion', accion);
        fd.append('legajos', JSON.stringify(selectedLegajos));
        
        if(accion === 'asignar_operador') {
            let val = document.getElementById('masivo_operador').value;
            if(val === '') return alert('Seleccione un operador');
            fd.append('operador_id', val);
        }
        if(accion === 'cambiar_estado') {
            let val = document.getElementById('masivo_estado').value;
            if(val === '') return alert('Seleccione un estado');
            fd.append('estado', val);
        }
        if(accion === 'eliminar' && !confirm(`¿Estás seguro de ELIMINAR ${selectedLegajos.length} clientes?`)) return;

        const res = await fetch('api_masivo.php', { method: 'POST', body: fd });
        const d = await res.json();
        if(d.success) { selectedLegajos = []; document.getElementById('masivo_operador').value = ''; document.getElementById('masivo_estado').value = ''; load(); stats(); } 
        else alert('Error: ' + d.message);
    }

    function switchTab(tab) { 
        if(document.getElementById('sec-dashboard')) document.getElementById('sec-dashboard').classList.toggle('hidden', tab !== 'dashboard');
        document.getElementById('sec-clientes').classList.toggle('hidden', tab !== 'clientes'); 
        if(document.getElementById('sec-usuarios')) document.getElementById('sec-usuarios').classList.toggle('hidden', tab !== 'usuarios');
        if(document.getElementById('sec-reportes')) document.getElementById('sec-reportes').classList.toggle('hidden', tab !== 'reportes');
        if(document.getElementById('sec-rendimiento')) document.getElementById('sec-rendimiento').classList.toggle('hidden', tab !== 'rendimiento');
        
        ['clientes', 'usuarios', 'dashboard', 'reportes', 'rendimiento'].forEach(t => {
            const btn = document.getElementById('btn-' + t);
            if (btn) {
                if (t === tab) {
                    btn.classList.add('tab-active');
                    btn.classList.remove('text-slate-400');
                } else {
                    btn.classList.remove('tab-active');
                    btn.classList.add('text-slate-400');
                }
            }
        });

        if (tab === 'usuarios') loadPersonal(); 
        if (tab === 'dashboard') loadDashboard();
        if (tab === 'reportes') {
            const hoy = new Date();
            const hace7 = new Date(); hace7.setDate(hoy.getDate() - 7);
            document.getElementById('rep-desde').value = hace7.toISOString().split('T')[0];
            document.getElementById('rep-hasta').value = hoy.toISOString().split('T')[0];
            cargarResumenGeneral();
            cambiarReporte('ranking');
        }
        if (tab === 'rendimiento') {
            cargarMiRendimiento();
        }
    }

    window.onload = async () => { 
        if(canAssign) { 
            const res = await fetch(api_usuarios+'?action=list'); 
            operadoresList = await res.json(); 
            const opsHTML = operadoresList.map(u => `<option value="${u.id}">👤 ${u.nombre}</option>`).join('');
            
            const fOp = document.getElementById('filter-operador');
            if(fOp) fOp.innerHTML = '<option value="0">Todos los Operadores</option><option value="-1">👤 Sin Asignar</option>' + opsHTML;
            document.getElementById('mAsignacion').innerHTML = '<option value="">👤 Sin Asignar</option>' + opsHTML;
            const mAsigDesk = document.getElementById('mAsignacionDesktop');
            if (mAsigDesk) mAsigDesk.innerHTML = '<option value="">👤 Sin Asignar</option>' + opsHTML; 
            const bOp = document.getElementById('masivo_operador');
            if(bOp) bOp.innerHTML += opsHTML;
            switchTab('dashboard');
        } else { switchTab('clientes'); }
        
        loadContactToggles();
        
        load(); 
        stats(); 
        loadNotificaciones();
        checkComunicado();
        
        // NUEVO: Motor de Short Polling. Consulta mensajes automáticamente cada 30 segundos.
        setInterval(checkComunicado, 30000);
    };

    let searchTimeout;
    document.getElementById('search').oninput = (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { load(); stats(); }, 400);
    };

    function saveContactToggles() {
        const prefs = {
            waWeb: document.getElementById('toggleWaWeb').checked,
            waApp: document.getElementById('toggleWaApp').checked,
            llamar: document.getElementById('toggleLlamar').checked,
            sms: document.getElementById('toggleSms').checked
        };
        localStorage.setItem('crm_contact_prefs', JSON.stringify(prefs));
    }
    
    function loadContactToggles() {
        try {
            const saved = localStorage.getItem('crm_contact_prefs');
            const prefs = saved ? JSON.parse(saved) : {waWeb: true, waApp: false, llamar: false, sms: false};
            document.getElementById('toggleWaWeb').checked = prefs.waWeb;
            document.getElementById('toggleWaApp').checked = prefs.waApp;
            document.getElementById('toggleLlamar').checked = prefs.llamar;
            document.getElementById('toggleSms').checked = prefs.sms;
        } catch(e) { }
    }

    const stats = async () => { 
        const q = document.getElementById('search').value;
        const est = document.getElementById('filter-estado').value;
        const op = document.getElementById('filter-operador')?.value || 0;
        const isMoto = document.getElementById('filtroMoto')?.checked ? 1 : 0; // NUEVO FILTRO MOTO STATS
        let url = `${api_clientes}stats&q=${encodeURIComponent(q)}&estado=${est}&operador_id=${op}&moto=${isMoto}`;

        try {
            const res = await fetch(url), d = await res.json(); 
            if(document.getElementById('stat-deuda')) document.getElementById('stat-deuda').innerText = '$' + parseFloat(d.deuda_total).toLocaleString('es-AR');
            if(document.getElementById('stat-total')) document.getElementById('stat-total').innerText = d.total_clientes;
            if(document.getElementById('stat-promesas')) document.getElementById('stat-promesas').innerText = d.promesas; 
            if(document.getElementById('stat-filtrados')) document.getElementById('stat-filtrados').innerText = d.clientes_filtrados;
        } catch(e) { console.error(e); }
    };

    async function cargarHistorial(legajo) {
        const chk = document.getElementById('chkVerOcultas');
        const verOcultas = (chk && chk.checked) ? 1 : 0;
        
        const hRes = await fetch(api_historial + encodeURIComponent(legajo) + '&ver_ocultas=' + verOcultas);
        const hData = await hRes.json();
        
        let maxId = hData.length > 0 ? Math.max(...hData.map(h => parseInt(h.id))) : 0;
        
        document.getElementById('mHisList').innerHTML = hData.map(h => {
            let fH = h.fecha.split(' ')[0].split('-').reverse().join('/') + ' ' + h.fecha.split(' ')[1].substring(0,5);
            let badgeH = `badge-${h.estado}`;
            let details = '';
            if (h.estado === 'promesa') {
                let dtPromesa = h.fecha_promesa ? h.fecha_promesa.split('-').reverse().join('/') : '';
                let msgRec = encodeURIComponent(`Hola, le recuerdo su promesa para el dia ${dtPromesa}\nOp: ${legajo}`).replace(/'/g, "%27");
                details = `<div class="mt-2 flex gap-4 text-[9px] font-black uppercase text-blue-500 bg-blue-50 p-2 rounded-xl items-center">
                    <span>💰 $${parseFloat(h.monto_promesa).toLocaleString('es-AR')}</span>
                    <span>📅 ${dtPromesa}</span>
                    <button type="button" onclick="enviarRecordatorio('${msgRec}')" title="Enviar recordatorio de promesa" class="ml-auto inline-flex items-center gap-1 bg-emerald-500 hover:bg-emerald-600 text-white px-2 py-0.5 rounded-full transition cursor-pointer">💬 Recordatorio</button>
                </div>`;
            }
            
            let btnEdit = '';
            let btnDel = '';
            let intentos = parseInt(h.intentos || 0);
            let isOculta = h.oculta == 1;
            
            let hJson = JSON.stringify(h).replace(/'/g, "\\'").replace(/"/g, '&quot;'); 

            if (canAssign) {
                // Si la gestión está oculta: solo el Admin puede restaurarla
                if (isOculta) {
                    if (isAdmin) {
                        btnDel = `<button type="button" onclick='restaurarGestion(${h.id}, "${legajo}")' class="text-emerald-600 hover:text-emerald-800 transition font-black">↩ Restaurar</button>`;
                    }
                } else {
                    // Gestión visible: se puede editar y "eliminar" (eliminación lógica)
                    btnEdit = `<button type="button" onclick="prepararEdicion(${hJson})" class="text-blue-500 hover:text-blue-700 transition">✏️ Editar</button>`;
                    btnDel = `<button type="button" onclick='eliminarGestion(${h.id}, "${legajo}")' class="text-rose-500 hover:text-rose-700 transition">🗑️ Eliminar</button>`;
                }
            } else if (isOperador && h.usuario_id == currentUserId && h.id == maxId && intentos < 3) {
                btnEdit = `<button type="button" onclick="prepararEdicion(${hJson})" class="text-blue-500 hover:text-blue-700 transition">✏️ Editar (${intentos}/3)</button>`;
            }
            
            let actionBtns = (btnEdit || btnDel) ? `<div class="flex gap-4 text-[10px] font-black uppercase mt-3 pt-3 border-t border-slate-100">${btnEdit} ${btnDel}</div>` : '';
            let ocultaBadge = isOculta ? `<span class="bg-rose-100 text-rose-600 px-2 py-0.5 rounded text-[8px] font-black uppercase ml-2">🗑️ Eliminada</span>` : '';

            return `<div class="p-5 rounded-3xl shadow-sm text-xs border mb-4 transition hover:shadow-md ${isOculta ? 'bg-slate-100 border-dashed border-slate-400 opacity-50' : 'bg-white border-slate-100'}">
                <div class="flex justify-between items-center font-black text-[9px] mb-3 uppercase tracking-widest text-slate-400"><span>📅 ${fH}</span><span>Op: ${h.operador}</span></div>
                <div class="mb-3"><span class="px-3 py-1 rounded-full text-[8px] font-black uppercase ${badgeH}">${h.estado.replace('_', ' ')}</span> ${ocultaBadge}</div>
                <p class="text-slate-600 font-semibold leading-relaxed">${h.observacion}</p>${details}
                ${actionBtns}
            </div>`;
        }).join('') || '<p class="text-center text-slate-300 py-10 font-black tracking-widest uppercase text-[10px]">Sin gestiones previas</p>';
    }

    async function openModal(c) {
        cancelarEdicion();
        // Rastrear posición en la lista
        const lista = window.listaClientes || [];
        window.clienteActualIdx = lista.findIndex(x => x.legajo === c.legajo);
        actualizarNavegacion();
        document.getElementById('mHis').innerHTML = '<p class="text-center py-10 opacity-30 text-[10px] font-black uppercase tracking-widest">Cargando...</p>';
        document.getElementById('mLegajoRaw').value = c.legajo; 
        document.getElementById('mRazon').innerText = c.razon_social; 
        
        let badgeMotoModal = c.moto == 1 ? `<span class="inline-flex items-center px-1.5 py-0.5 ml-2 text-[9px] font-black uppercase tracking-widest text-white bg-red-600 rounded-full animate-pulse border border-red-800 shadow-sm" title="Deuda Moto">🚨 MOTO</span>` : '';
        document.getElementById('mLegajo').innerHTML = `ID: ${c.l_entidad_id || '-'} • Legajo: ${c.legajo} ${badgeMotoModal}`;
        
        document.getElementById('mSucursal').innerText = c.sucursal || 'Central';
        // Sincronizar campo de sucursal en mobile
        const mSucursalMob = document.getElementById('mSucursalMobile');
        if (mSucursalMob) mSucursalMob.innerText = c.sucursal || 'Central';
        if (canAssign) {
            document.getElementById('mAsignacion').value = c.operador_id || '';
            const mAsigDesk = document.getElementById('mAsignacionDesktop');
            if (mAsigDesk) mAsigDesk.value = c.operador_id || '';
        }
        
        const warnBadge = document.getElementById('mWarningOtroOp');
        if(isOperador && c.operador_id != currentUserId) {
            warnBadge.classList.remove('hidden');
            warnBadge.innerText = c.operador_id ? `⚠️ Asignado a ${c.operador_asignado?.split(' ')[0] || 'otro operador'} - Tu gestión quedará a tu nombre` : `⚠️ Sin asignar - Tu gestión quedará a tu nombre`;
        } else {
            warnBadge.classList.add('hidden');
        }

        document.getElementById('mTotal').innerText = `$${parseFloat(c.total_vencido).toLocaleString('es-AR')}`;
        document.getElementById('mDias').innerText = c.dias_atraso || '0'; 
        document.getElementById('mCuotas').innerText = c.c_cuotas || '0';
        document.getElementById('mDomicilio').innerText = c.domicilio || '-'; 
        // Teléfonos: genera un botón WhatsApp independiente por cada número
        const telefonos = c.telefonos || '';
        const operadorNombre = <?= json_encode($nombre_usuario) ?>;
        const legajoCliente = c.legajo || '';
        const msgs = [
            `Hola, le escribo de Landy Confort para informarle que registra cuotas vencidas.\n*Op: ${legajoCliente}*\nQuedo atento a su consulta, escríbame para más información.`,
            `Hola, soy de Landy Confort. Le aviso que tiene cuotas pendientes de pago.\n*Op: ${legajoCliente}*\nQuedo atento a su consulta. Puede escribirme para más información.`,
            `Hola, desde Landy Confort nos comunicamos por cuotas vencidas en su cuenta.\n*Op: ${legajoCliente}*\nQuedo atento a su consulta, escríbame para más información.`,
            `Hola, le contacto de Landy Confort para notificarle cuotas en mora.\n*Op: ${legajoCliente}*\nQuedo atento a su consulta. Escríbame para más información.`,
            `Hola, le hablamos de Landy Confort por cuotas vencidas registradas.\n*Op: ${legajoCliente}*\nQuedo atento a su consulta, puede escribirme para más información.`
        ];
        const mensaje = msgs[Math.floor(Math.random() * msgs.length)];
        const msgEnc = encodeURIComponent(mensaje);
        const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);
        
        // Separadores: guión normal, guión largo, barra, coma, punto y coma
        const numerosRaw = telefonos.split(/[-–\/,;]+/).map(n => n.trim()).filter(n => n.length > 0);
        window.currentClientPhones = numerosRaw;
        const waContainer = document.getElementById('mWALinks');
        waContainer.innerHTML = '<span class="text-slate-400">📞</span>'; // reset
        if (numerosRaw.length === 0 || telefonos === '') {
            waContainer.innerHTML += '<span class="text-slate-800 font-bold">-</span>';
        } else {
            numerosRaw.forEach(raw => {
                const soloDigitos = raw.replace(/\D/g, '');
                
                const spanNro = document.createElement('span');
                spanNro.className = 'text-slate-800 font-bold';
                spanNro.textContent = raw;
                waContainer.appendChild(spanNro);
                
                if (soloDigitos.length >= 8) {
                    let nroLimpio = soloDigitos.replace(/^0/, '');
                    if (!nroLimpio.startsWith('549')) nroLimpio = '549' + nroLimpio;
                    
                    // Número con formato para tel: y sms: (prefijo +54 9)
                    const nroTel = '+' + nroLimpio;
                    const smsBody = encodeURIComponent(`Hola, me comunico de Landy Confort. Ref: ${legajoCliente}`);
                    
                    // Leer preferencias guardadas (en localStorage o estado de los checks)
                    let prefs = {waWeb: true, waApp: false, llamar: false, sms: false};
                    try { const saved = localStorage.getItem('crm_contact_prefs'); if (saved) prefs = JSON.parse(saved); } catch(e) {}
                    
                    if (isMobile) {
                        // En celulares, mostramos la opción nativa si el usuario quiere usar waWeb o waApp
                        if (prefs.waWeb || prefs.waApp) {
                            const btnMobil = document.createElement('a');
                            btnMobil.href = `https://api.whatsapp.com/send?phone=${nroLimpio}&text=${msgEnc}`;
                            btnMobil.target = '_blank';
                            btnMobil.title = `WhatsApp Móvil ${raw}`;
                            btnMobil.className = 'inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full transition ml-1';
                            btnMobil.innerHTML = '💬 WA';
                            waContainer.appendChild(btnMobil);
                        }
                    } else {
                        // Desktop
                        if (prefs.waApp) {
                            const btnApp = document.createElement('a');
                            btnApp.href = `whatsapp://send?phone=${nroLimpio}&text=${msgEnc}`;
                            btnApp.title = `Abrir en WhatsApp Desktop (PC)`;
                            btnApp.className = 'inline-flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full transition ml-1';
                            btnApp.innerHTML = '💻 APP';
                            waContainer.appendChild(btnApp);
                        }
                        
                        if (prefs.waWeb) {
                            const btnWeb = document.createElement('a');
                            btnWeb.href = `https://web.whatsapp.com/send?phone=${nroLimpio}&text=${msgEnc}`;
                            btnWeb.target = 'whatsapp_crm';
                            btnWeb.title = `Abrir en WhatsApp Web`;
                            btnWeb.className = 'inline-flex items-center gap-1 bg-green-500 hover:bg-green-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full transition ml-1';
                            btnWeb.innerHTML = '🌐 WEB';
                            waContainer.appendChild(btnWeb);
                        }
                    }
                    
                    if (prefs.llamar) {
                        const btnCall = document.createElement('a');
                        btnCall.href = `tel:${nroTel}`;
                        btnCall.title = `Llamar a ${raw}`;
                        btnCall.className = 'inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full transition ml-1';
                        btnCall.innerHTML = '📞 LLAMAR';
                        waContainer.appendChild(btnCall);
                    }
                    
                    if (prefs.sms) {
                        const btnSms = document.createElement('a');
                        btnSms.href = `sms:${nroTel}?body=${smsBody}`;
                        btnSms.title = `Enviar SMS a ${raw}`;
                        btnSms.className = 'inline-flex items-center gap-1 bg-purple-700 hover:bg-purple-800 text-white text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full transition ml-1';
                        btnSms.innerHTML = '✉️ SMS';
                        waContainer.appendChild(btnSms);
                    }
                }
                
                if (raw !== numerosRaw[numerosRaw.length - 1]) {
                    const sep = document.createElement('span');
                    sep.className = 'text-slate-300 font-bold ml-1 mr-1';
                    sep.textContent = '•';
                    waContainer.appendChild(sep);
                }
            });
        }
        document.getElementById('mDocumento').innerText = c.nro_documento || '-';
        document.getElementById('mUltimo').innerText = c.ultimo_pago?.split('-').reverse().join('/') || 'Nunca';
        document.getElementById('mVenc').innerText = c.vencimiento?.split('-').reverse().join('/') || '-';
        document.getElementById('mEst').value = c.estado_actual === 'sin_gestion' ? 'promesa' : c.estado_actual; 
        document.getElementById('mMon').value = c.monto_promesa || ''; 
        document.getElementById('mFec').value = c.fecha_promesa || '';
        
        document.getElementById('mEst').dispatchEvent(new Event('change'));

        const formElements = document.querySelectorAll('#gForm input, #gForm select, #gForm textarea');
        const btnGuardar = document.querySelector('#gForm button[type="submit"]');
        
        if (isOperador && c.estado_actual === 'al_dia') {
            formElements.forEach(el => el.disabled = true);
            btnGuardar.disabled = true;
            btnGuardar.innerText = '🔒 Bloqueado (Al Día)';
            btnGuardar.classList.add('opacity-50', 'cursor-not-allowed');
            btnGuardar.classList.remove('hover:bg-blue-700');
        } else {
            formElements.forEach(el => el.disabled = false);
            btnGuardar.disabled = false;
            btnGuardar.innerText = 'Guardar Gestión';
            btnGuardar.classList.remove('opacity-50', 'cursor-not-allowed');
            btnGuardar.classList.add('hover:bg-blue-700');
        }

        // Solo el Admin ve el toggle "Ver eliminados" para auditoría
        let verOcultasHTML = isAdmin ? `<div class="mb-4 flex justify-between items-center bg-slate-100 px-4 py-3 rounded-2xl"><span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">🔍 Ver gestiones eliminadas</span><input type="checkbox" id="chkVerOcultas" onchange="cargarHistorial('${c.legajo}')" class="w-4 h-4 rounded text-blue-600"></div>` : '';
        document.getElementById('mHis').innerHTML = verOcultasHTML + `<div id="mHisList"></div>`;
        
        await cargarHistorial(c.legajo);

        document.getElementById('modal').classList.replace('hidden', 'flex'); 
        setTimeout(() => document.getElementById('mContent').classList.replace('scale-95', 'scale-100'), 10);
        // En mobile: siempre abrir en tab Gestión
        switchModalTab('gestion');
    }

    function closeModal() {
        const m = document.getElementById('modal');
        document.getElementById('mContent').classList.replace('scale-100', 'scale-95');
        setTimeout(() => m.classList.replace('flex', 'hidden'), 200);
    }

    function actualizarNavegacion() {
        const lista = window.listaClientes || [];
        const idx   = window.clienteActualIdx ?? -1;
        const btnPrev = document.getElementById('btnNavPrev');
        const btnNext = document.getElementById('btnNavNext');
        const ind     = document.getElementById('navIndicator');
        if (!btnPrev || !btnNext || !ind) return;
        ind.textContent = lista.length > 0 ? `${idx + 1} / ${lista.length}` : '— / —';
        btnPrev.disabled = idx <= 0;
        btnNext.disabled = idx < 0 || idx >= lista.length - 1;
    }

    function navegarCliente(dir) {
        const lista = window.listaClientes || [];
        const idx   = (window.clienteActualIdx ?? -1) + dir;
        if (idx < 0 || idx >= lista.length) return;
        openModal(lista[idx]);
    }

    /**
     * Alterna entre el panel de Gestión y el de Historial en mobile.
     * En desktop ambos paneles son siempre visibles (side by side).
     */
    function switchModalTab(tab) {
        const isMobileView = window.innerWidth < 768;
        const panelGestion   = document.getElementById('panelGestion');
        const panelHistorial = document.getElementById('panelHistorial');
        const tabGestion     = document.getElementById('tabGestion');
        const tabHistorial   = document.getElementById('tabHistorial');

        if (!isMobileView) {
            // Desktop: ambos paneles siempre visibles
            panelHistorial.classList.remove('hidden');
            panelHistorial.classList.add('flex');
            return;
        }

        if (tab === 'gestion') {
            panelGestion.classList.remove('hidden');
            panelHistorial.classList.add('hidden');
            panelHistorial.classList.remove('flex');
            if (tabGestion)   { tabGestion.classList.add('bg-white', 'text-blue-600', 'shadow-sm'); tabGestion.classList.remove('text-slate-400'); }
            if (tabHistorial) { tabHistorial.classList.remove('bg-white', 'text-blue-600', 'shadow-sm'); tabHistorial.classList.add('text-slate-400'); }
        } else {
            panelHistorial.classList.remove('hidden');
            panelHistorial.classList.add('flex');
            panelGestion.classList.add('hidden');
            if (tabHistorial) { tabHistorial.classList.add('bg-white', 'text-blue-600', 'shadow-sm'); tabHistorial.classList.remove('text-slate-400'); }
            if (tabGestion)   { tabGestion.classList.remove('bg-white', 'text-blue-600', 'shadow-sm'); tabGestion.classList.add('text-slate-400'); }
        }
    }

    function prepararEdicion(h) {
        document.getElementById('gAction').value = 'edit';
        document.getElementById('gId').value = h.id;
        document.getElementById('mEst').value = h.estado;
        document.getElementById('mFec').value = h.fecha_promesa || '';
        document.getElementById('mMon').value = h.monto_promesa || '';
        document.querySelector('textarea[name="observacion"]').value = h.observacion || '';
        
        document.getElementById('mEst').dispatchEvent(new Event('change'));
        
        document.getElementById('editWarning').classList.remove('hidden');
        const btnGuardar = document.getElementById('btnSubmitGestion');
        btnGuardar.innerText = 'Actualizar Gestión';
        btnGuardar.classList.replace('bg-blue-600', 'bg-amber-500');
        btnGuardar.classList.replace('hover:bg-blue-700', 'hover:bg-amber-600');
    }

    function cancelarEdicion() {
        document.getElementById('gAction').value = 'insert';
        document.getElementById('gId').value = '';
        document.querySelector('textarea[name="observacion"]').value = '';
        document.getElementById('editWarning').classList.add('hidden');
        
        const btnGuardar = document.getElementById('btnSubmitGestion');
        btnGuardar.innerText = 'Guardar Gestión';
        btnGuardar.classList.replace('bg-amber-500', 'bg-blue-600');
        btnGuardar.classList.replace('hover:bg-amber-600', 'hover:bg-blue-700');
    }

    async function eliminarGestion(id, legajo) {
        if (!confirm('¿Eliminar esta gestión? Quedará oculta y solo el Administrador podrá restaurarla.')) return;
        let fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        await fetch(api_gestion, { method:'POST', body:fd });
        await cargarHistorial(legajo);
        load(); stats(); if(canAssign) loadDashboard(); loadNotificaciones();
    }

    async function restaurarGestion(id, legajo) {
        if (!confirm('¿Restaurar esta gestión? Volverá a ser visible para todos.')) return;
        let fd = new FormData();
        fd.append('action', 'restore');
        fd.append('id', id);
        const res = await fetch(api_gestion, { method: 'POST', body: fd });
        const d = await res.json();
        if (d.success) {
            await cargarHistorial(legajo);
            load(); stats(); if(canAssign) loadDashboard(); loadNotificaciones();
        } else {
            alert('Error al restaurar: ' + (d.message || 'Error desconocido'));
        }
    }

    if (document.getElementById('mEst') && document.getElementById('mFec')) {
        document.getElementById('mEst').addEventListener('change', (e) => {
            const fInput = document.getElementById('mFec');
            if (e.target.value === 'promesa' || e.target.value === 'llamar') {
                fInput.setAttribute('required', 'true');
                fInput.classList.replace('bg-slate-50', 'bg-rose-50');
                fInput.classList.replace('border-slate-200', 'border-rose-300');
            } else {
                fInput.removeAttribute('required');
                fInput.classList.replace('bg-rose-50', 'bg-slate-50');
                fInput.classList.replace('border-rose-300', 'border-slate-200');
            }
        });
    }

    if(document.getElementById('gForm')){
        document.getElementById('gForm').onsubmit = async (e) => {
            e.preventDefault(); 
            // Manejar Tips
            const val = e.target.value;
            const tipsContainer = document.getElementById('tips-estado');
            const tipsText = document.getElementById('tips-texto');
            if (tipsContainer) {
                tipsContainer.classList.remove('hidden');
                if (val === 'no_responde') tipsText.innerHTML = "Intentá contactar por <b>WhatsApp</b> con un mensaje breve: <i>'Le llamé de Roque, necesito hablar con usted'</i>. Mejorá tus chances llamando a las 18-20hs.";
                else if (val === 'no_corresponde') tipsText.innerHTML = "Si te atiende otra persona, intentá averiguar <b>un número alternativo o paradero</b> antes de cortar. Marcá para actualizar los datos en oficina.";
                else if (val === 'llamar') tipsText.innerHTML = "Agendá un <b>horario específico</b> y cumplilo. La puntualidad al volver a llamar genera presión positiva y seriedad.";
                else if (val === 'promesa') tipsText.innerHTML = "Repetí el <b>monto exacto y fecha</b> al finalizar la llamada: <i>'Entonces confirmamos $XXX para este viernes 15'</i>. Creá compromiso verbal.";
                else if (val === 'al_dia') tipsText.innerHTML = "¡Excelente! Agradecé el pago y cordialmente da por finalizado el reclamo.";
                else tipsContainer.classList.add('hidden');
            }

            const estadoSelec = document.getElementById('mEst').value;
            const fechaSelec = document.getElementById('mFec').value;
            if ((estadoSelec === 'promesa' || estadoSelec === 'llamar') && !fechaSelec) {
                alert('⚠️ ACCIÓN REQUERIDA: Debes ingresar una fecha de seguimiento para poder guardar esta gestión.');
                document.getElementById('mFec').focus();
                return;
            }
            const btn = document.getElementById('btnSubmitGestion'); 
            const textOrig = btn.innerText;
            btn.disabled = true; 
            btn.innerText = 'Guardando...';
            try {
                const fd = new FormData(e.target);
                const res = await fetch(api_gestion, { method: 'POST', body: fd });
                const d = await res.json();
                if (d.success) { 
                    load(); 
                    stats(); 
                    loadNotificaciones();
                    if(canAssign) loadDashboard();
                    // Avanzar al siguiente cliente automáticamente
                    const lista = window.listaClientes || [];
                    const siguienteIdx = (window.clienteActualIdx ?? -1) + 1;
                    if (siguienteIdx < lista.length) {
                        openModal(lista[siguienteIdx]);
                    } else {
                        closeModal();
                    }
                } else { alert("Error: " + d.message); }
            } catch(err) { alert("Error de conexión al guardar."); } 
            finally { btn.disabled = false; btn.innerText = textOrig; }
        };
    }

    async function cambiarAsignacion(uid) {
        const legajo = document.getElementById('mLegajoRaw').value;
        if (!legajo) return;
        // Sincronizar ambos selectores (mobile y desktop)
        const selMob  = document.getElementById('mAsignacion');
        const selDesk = document.getElementById('mAsignacionDesktop');
        if (selMob  && selMob.value  !== uid) selMob.value  = uid;
        if (selDesk && selDesk.value !== uid) selDesk.value = uid;
        const fd = new FormData();
        fd.append('legajo', legajo);
        fd.append('usuario_id', uid);
        await fetch(api_clientes + 'assign', { method: 'POST', body: fd });
        load(); stats(); if (canAssign) loadDashboard();
    }
    async function cambiarAsignacionRapida(legajo, uid) { const fd = new FormData(); fd.append('legajo', legajo); fd.append('usuario_id', uid); await fetch(api_clientes+'assign', {method:'POST', body:fd}); stats(); if(canAssign) loadDashboard(); }
    function enviarRecordatorio(msgEnc) {
        if (!window.currentClientPhones || window.currentClientPhones.length === 0) {
            alert('El cliente no tiene un número de teléfono registrado.');
            return;
        }
        
        let telRaw = window.currentClientPhones[0];
        if (window.currentClientPhones.length > 1) {
            let opciones = window.currentClientPhones.map((t, i) => `${i + 1}: ${t}`).join('\n');
            let sel = prompt(`El cliente tiene varios teléfonos. Ingrese el número de la opción que desea usar para el recordatorio:\n\n${opciones}\n\nO ingrese un número distinto directamente:`, '1');
            if (!sel) return; // Se canceló
            let idx = parseInt(sel) - 1;
            if (!isNaN(idx) && idx >= 0 && idx < window.currentClientPhones.length) {
                telRaw = window.currentClientPhones[idx];
            } else {
                telRaw = sel; // si ingresó un celular a mano
            }
        }
        
        const soloDigitos = telRaw.replace(/\D/g, '');
        if (soloDigitos.length < 8) {
            alert('El número de teléfono parece inválido para enviar WhatsApp.');
            return;
        }
        let nroLimpio = soloDigitos.replace(/^0/, '');
        if (!nroLimpio.startsWith('549')) nroLimpio = '549' + nroLimpio;
        
        let prefs = {waWeb: true, waApp: false};
        try { const saved = localStorage.getItem('crm_contact_prefs'); if(saved) prefs = JSON.parse(saved); } catch(e){}
        const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);
        
        let url = '';
        if (isMobile) {
            url = `https://api.whatsapp.com/send?phone=${nroLimpio}&text=${msgEnc}`;
        } else {
            if (prefs.waApp) {
                url = `whatsapp://send?phone=${nroLimpio}&text=${msgEnc}`;
            } else {
                url = `https://web.whatsapp.com/send?phone=${nroLimpio}&text=${msgEnc}`;
            }
        }
        
        if (isMobile || prefs.waApp) {
            window.open(url, '_blank');
        } else {
            window.open(url, 'whatsapp_crm');
        }
    }

    function closeModal() { document.getElementById('mContent').classList.replace('scale-100', 'scale-95'); setTimeout(() => document.getElementById('modal').classList.replace('flex', 'hidden'), 300); }
    
    function openUserModal() { document.getElementById('uId').value = ''; document.getElementById('userForm').reset(); document.getElementById('modalUser').classList.replace('hidden', 'flex'); }
    function editarUsuario(u) { document.getElementById('uId').value = u.id; document.getElementById('uNom').value = u.nombre; document.getElementById('uMail').value = u.usuario; document.getElementById('uRol').value = u.rol; document.getElementById('modalUser').classList.replace('hidden', 'flex'); }
    async function eliminarUsuario(id, nom) { if(!confirm(`¿Eliminar al operador ${nom}?`)) return; const fd = new FormData(); fd.append('id', id); await fetch(api_usuarios+'?action=delete', {method:'POST', body:fd}); loadPersonal(); if(canAssign) loadDashboard(); }
    if(document.getElementById('userForm')){ document.getElementById('userForm').onsubmit = async (e) => { e.preventDefault(); await fetch(api_usuarios+'?action=save', {method:'POST', body:new FormData(e.target)}); document.getElementById('modalUser').classList.replace('flex', 'hidden'); loadPersonal(); if(canAssign) loadDashboard(); }; }
    
    async function subirCSV(input) { 
        if(!input.files[0]) return; 
        const overlay = document.createElement('div');
        overlay.id = 'importOverlay';
        overlay.innerHTML = `<div style="position:fixed;inset:0;background:rgba(15,23,42,0.8);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;flex-direction:column;color:white;">
            <div style="width:48px;height:48px;border:4px solid #3b82f6;border-top-color:transparent;border-radius:50%;animation:spin 1s linear infinite;margin-bottom:1rem;"></div>
            <p style="font-weight:900;text-transform:uppercase;letter-spacing:2px;font-size:12px;">Sincronizando Base de Datos...</p>
        </div><style>@keyframes spin{to{transform:rotate(360deg)}}</style>`;
        document.body.appendChild(overlay);

        const fd = new FormData(); 
        fd.append('file', input.files[0]); 
        try { 
            const res = await fetch('api_importar_csv.php', {method:'POST', body:fd}); 
            const d = await res.json(); 
            if (document.getElementById('importOverlay')) document.body.removeChild(overlay);
            if(d.success) { 
                const msg = d.count + (d.errores && d.errores.length ? '\n\nErrores:\n' + d.errores.join('\n') : '');
                const resDiv = document.createElement('div');
                resDiv.innerHTML = `
                    <div style="position:fixed;inset:0;background:rgba(15,23,42,0.8);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;z-index:9999;">
                        <div style="background:white;padding:2.5rem;border-radius:2rem;text-align:center;max-width:400px;width:90%;">
                            <div style="background:#dcfce7;color:#166534;width:60px;height:60px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:24px;">✅</div>
                            <p style="font-weight:900;font-size:14px;color:#1e293b;text-transform:uppercase;letter-spacing:.1em;margin-bottom:1rem;">Importación completada</p>
                            <p style="font-size:13px;color:#475569;font-weight:600;white-space:pre-line;margin-bottom:1.5rem;">${msg}</p>
                            <button onclick="this.closest('div').parentElement.parentElement.remove();load();stats();loadNotificaciones();if(canAssign) loadDashboard();" style="background:#2563eb;color:white;border:none;padding:.75rem 2rem;border-radius:1rem;font-weight:900;font-size:11px;text-transform:uppercase;cursor:pointer;letter-spacing:.1em;">Aceptar</button>
                        </div>
                    </div>`;
                document.body.appendChild(resDiv);
            } else { alert('Error en la importación: ' + (d.message || 'Error desconocido')); }
        } catch(e) { 
            if (document.getElementById('importOverlay')) document.body.removeChild(overlay);
            alert("Error crítico al procesar archivo."); 
        } finally { input.value = ""; }
    }

    async function subirCSVAsignaciones(input) { 
        if(!input.files[0]) return; 
        const fd = new FormData(); fd.append('file', input.files[0]); 
        const ogBtn = input.previousElementSibling; const ogText = ogBtn.innerText;
        ogBtn.innerText = 'Asignando...'; ogBtn.classList.add('animate-pulse');
        try { 
            const res = await fetch('api_importar_asignaciones.php', {method:'POST', body:fd}); const d = await res.json(); 
            if(d.success) { alert(d.count); load(); stats(); loadNotificaciones(); if(canAssign) loadDashboard(); } else { alert("Error: " + d.message); }
        } catch(e) { alert("Error al procesar el archivo de asignaciones."); } 
        finally { ogBtn.innerText = ogText; ogBtn.classList.remove('animate-pulse'); input.value = ""; }
    }

    // ─────────────────────────────────────────────────────────────────
    // MÓDULO DE REPORTES
    // ─────────────────────────────────────────────────────────────────

    let reporteActual = 'ranking';
    let datosReporteActual = {};
    let filtroReportes = {
        desde: (() => { const d = new Date(); d.setDate(d.getDate() - 7); return d.toISOString().split('T')[0]; })(),
        hasta: new Date().toISOString().split('T')[0]
    };

    function aplicarPeriodo() {
        const periodo = document.getElementById('rep-periodo').value;
        const hoy = new Date();
        let desde = new Date();
        if (periodo === 'hoy') { desde = new Date(hoy); }
        else if (periodo === 'semana') { desde.setDate(hoy.getDate() - 7); }
        else if (periodo === 'mes') { desde.setDate(hoy.getDate() - 30); }
        else if (periodo === 'trimestre') { desde.setDate(hoy.getDate() - 90); }
        else { return; }
        filtroReportes.desde = desde.toISOString().split('T')[0];
        filtroReportes.hasta = hoy.toISOString().split('T')[0];
        document.getElementById('rep-desde').value = filtroReportes.desde;
        document.getElementById('rep-hasta').value = filtroReportes.hasta;
        cargarResumenGeneral();
        cambiarReporte(reporteActual);
    }

    function actualizarFiltros() {
        filtroReportes.desde = document.getElementById('rep-desde').value || filtroReportes.desde;
        filtroReportes.hasta = document.getElementById('rep-hasta').value || filtroReportes.hasta;
    }

    async function cargarResumenGeneral() {
        actualizarFiltros();
        try {
            const res = await fetch(`api_reportes.php?action=resumen_general&fecha_desde=${filtroReportes.desde}&fecha_hasta=${filtroReportes.hasta}`);
            const d = await res.json();
            if (d.success) {
                document.getElementById('rep-op-activos').innerText = d.data.operadores_activos || 0;
                document.getElementById('rep-op-totales').innerText = `de ${d.data.operadores_totales || 0} totales`;
                document.getElementById('rep-gestiones').innerText = d.data.total_gestiones || 0;
                document.getElementById('rep-clientes').innerText = d.data.clientes_unicos || 0;
                document.getElementById('rep-al-dia').innerText = d.data.al_dia_totales || 0;
            }
        } catch (e) { console.error('Error cargando resumen:', e); }
    }

    async function cambiarReporte(tipo) {
        reporteActual = tipo;
        actualizarFiltros();

        // Actualizar tabs
        document.querySelectorAll('[id^="rep-tab-"]').forEach(btn => {
            btn.classList.remove('text-blue-600', 'border-blue-600', 'tab-active');
            btn.classList.add('text-slate-400', 'border-transparent');
        });
        const tabActivo = document.getElementById(`rep-tab-${tipo}`);
        if (tabActivo) {
            tabActivo.classList.add('text-blue-600', 'border-blue-600', 'tab-active');
            tabActivo.classList.remove('text-slate-400', 'border-transparent');
        }

        document.getElementById('rep-contenido').innerHTML = '<p class="text-center py-20 text-slate-400 font-bold text-[10px] uppercase tracking-widest">Cargando datos...</p>';

        const mapAccion = {
            ranking: 'ranking_operadores',
            productividad: 'productividad',
            efectividad: 'efectividad_al_dia',
            matriz: 'matriz_cruce',
            clientes: 'clientes_mas_gestionados',
            diario: 'resumen_diario'
        };

        try {
            const res = await fetch(`api_reportes.php?action=${mapAccion[tipo]}&fecha_desde=${filtroReportes.desde}&fecha_hasta=${filtroReportes.hasta}`);
            const d = await res.json();
            if (d.success) {
                datosReporteActual = d.data;
                renderizarReporte(tipo, d.data);
            } else {
                document.getElementById('rep-contenido').innerHTML = `<p class="text-center py-10 text-rose-500 font-bold">⚠️ Error: ${d.error || d.message}</p>`;
            }
        } catch (e) {
            document.getElementById('rep-contenido').innerHTML = `<p class="text-center py-10 text-rose-500 font-bold">❌ Error de conexión</p>`;
        }
    }

    function renderizarReporte(tipo, datos) {
        let html = '';
        if (!datos || (Array.isArray(datos) && datos.length === 0)) {
            document.getElementById('rep-contenido').innerHTML = `<p class="text-center py-20 text-slate-400 font-bold text-[10px] uppercase tracking-widest">Sin datos para el período seleccionado.</p>`;
            return;
        }

        if (tipo === 'ranking') {
            html = `<h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">🏆 Ranking de Operadores</h3>
            <div class="overflow-x-auto custom-scroll"><table class="w-full text-sm">
                <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-widest"><tr>
                    <th class="px-6 py-4 text-left">Posición</th><th class="px-6 py-4 text-left">Operador</th>
                    <th class="px-6 py-4 text-center">Gestiones</th><th class="px-6 py-4 text-center">Clientes</th>
                    <th class="px-6 py-4 text-center">Promesas</th><th class="px-6 py-4 text-center">Al Día</th>
                    <th class="px-6 py-4 text-center">Efectividad</th><th class="px-6 py-4 text-center">Conv. Al Día</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                ${datos.map((op, i) => {
                    const medal = i === 0 ? '🥇' : (i === 1 ? '🥈' : (i === 2 ? '🥉' : ''));
                    const bg = i === 0 ? 'bg-amber-50' : (i === 1 ? 'bg-slate-50' : (i === 2 ? 'bg-orange-50' : ''));
                    const eff = op.efectividad_pct || 0;
                    const col = eff >= 30 ? 'text-emerald-600 bg-emerald-50' : (eff >= 15 ? 'text-amber-600 bg-amber-50' : 'text-rose-600 bg-rose-50');
                    return `<tr class="${bg} hover:shadow-sm transition">
                        <td class="px-6 py-5 font-black text-slate-800">${medal} #${i+1}</td>
                        <td class="px-6 py-5 font-bold text-slate-700">${op.nombre}</td>
                        <td class="px-6 py-5 text-center font-black text-blue-600">${op.total_gestiones || 0}</td>
                        <td class="px-6 py-5 text-center font-bold text-slate-600">${op.clientes_gestionados || 0}</td>
                        <td class="px-6 py-5 text-center font-bold text-emerald-600">${op.promesas_logradas || 0}</td>
                        <td class="px-6 py-5 text-center font-bold text-rose-600">${op.clientes_al_dia || 0}</td>
                        <td class="px-6 py-5 text-center font-black ${col}">${eff}%</td>
                        <td class="px-6 py-5 text-center font-black text-blue-600">${op.tasa_conversion_al_dia || 0}%</td>
                    </tr>`;
                }).join('')}
                </tbody>
            </table></div>`;
        }

        if (tipo === 'productividad') {
            html = `<h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">📊 Productividad Operativa</h3>
            <div class="overflow-x-auto custom-scroll"><table class="w-full text-sm">
                <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-widest"><tr>
                    <th class="px-4 py-3 text-left">Operador</th><th class="px-4 py-3 text-center">Días Activos</th>
                    <th class="px-4 py-3 text-center">Gestiones</th><th class="px-4 py-3 text-center">Prom/Día</th>
                    <th class="px-4 py-3 text-center">Última Gestión</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                ${datos.map(op => `<tr class="hover:bg-blue-50/30">
                    <td class="px-4 py-3 font-bold text-slate-700 text-xs">${op.operador}</td>
                    <td class="px-4 py-3 text-center font-bold text-blue-600">${op.dias_activos}</td>
                    <td class="px-4 py-3 text-center font-black text-slate-800">${op.total_gestiones}</td>
                    <td class="px-4 py-3 text-center font-bold text-emerald-600">${op.promedio_gestiones_por_dia}</td>
                    <td class="px-4 py-3 text-center text-[9px] text-slate-500">${op.ultima_gestion ? op.ultima_gestion.split(' ')[0] : '-'}</td>
                </tr>`).join('')}
                </tbody>
            </table></div>`;
        }

        if (tipo === 'efectividad') {
            html = `<h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">✅ Efectividad (Clientes Al Día)</h3>
            <div class="space-y-4">
            ${datos.map((op, i) => {
                const pct = parseFloat(op.tasa_al_dia_pct || 0);
                const color = pct >= 30 ? 'emerald' : (pct >= 15 ? 'amber' : 'rose');
                return `<div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <p class="font-black text-slate-800 text-sm">${i+1}. ${op.nombre}</p>
                            <p class="text-[10px] text-slate-500 uppercase tracking-widest font-bold">Gestionó ${op.clientes_gestionados} clientes</p>
                        </div>
                        <span class="px-4 py-2 rounded-xl font-black text-[11px] uppercase bg-${color}-50 text-${color}-600">${pct.toFixed(1)}%</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-${color}-500 h-full rounded-full transition-all" style="width:${Math.min(pct,100)}%"></div>
                    </div>
                    <div class="grid grid-cols-3 gap-4 mt-4 text-[10px] font-bold text-slate-600">
                        <div>🎯 Al Día: <span class="text-emerald-600">${op.al_dia_logrados}</span></div>
                        <div>💬 Promesas: <span class="text-blue-600">${op.promesas}</span></div>
                        <div>❌ No Responde: <span class="text-slate-600">${op.no_responde}</span></div>
                    </div>
                </div>`;
            }).join('')}
            </div>`;
        }

        if (tipo === 'matriz') {
            const estados = datos.length > 0 ? Object.keys(datos[0]).filter(k => !['operador', 'operador_id', 'total'].includes(k)) : [];
            html = `<h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">⚙️ Matriz Operador × Estado</h3>
            <div class="overflow-x-auto custom-scroll"><table class="w-full text-sm border-collapse">
                <thead class="bg-slate-900 text-white"><tr>
                    <th class="px-4 py-3 text-left font-black text-[10px] tracking-widest">Operador</th>
                    ${estados.map(e => `<th class="px-3 py-3 text-center font-black text-[9px] tracking-widest">${e.toUpperCase()}</th>`).join('')}
                    <th class="px-4 py-3 text-center font-black text-[10px] tracking-widest bg-blue-600">TOTAL</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                ${datos.map(row => `<tr class="hover:bg-blue-50">
                    <td class="px-4 py-3 font-bold text-slate-700 text-xs sticky left-0 bg-white z-10">${row.operador}</td>
                    ${estados.map(e => `<td class="px-3 py-3 text-center font-bold ${row[e] > 0 ? 'bg-blue-50 text-blue-600' : 'text-slate-400'}">${row[e]}</td>`).join('')}
                    <td class="px-4 py-3 text-center font-black bg-blue-100 text-blue-900">${row.total}</td>
                </tr>`).join('')}
                </tbody>
            </table></div>`;
        }

        if (tipo === 'clientes') {
            html = `<h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">👥 Top Clientes Más Gestionados</h3>
            <div class="space-y-3">
            ${datos.slice(0, 20).map((c) => `<div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-black text-slate-800">${c.razon_social}</p>
                        <p class="text-[9px] text-blue-600 font-bold uppercase">${c.legajo}</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 font-black text-[9px] tracking-widest">👤 ${c.operadores_distintos} Op</span>
                </div>
                <div class="grid grid-cols-4 gap-3 text-[10px] font-bold text-slate-600">
                    <div>📞 Gestiones: <span class="text-slate-800 font-black">${c.total_gestiones}</span></div>
                    <div>💰 Vencido: <span class="text-rose-600">$${parseFloat(c.total_vencido).toLocaleString('es-AR')}</span></div>
                    <div>⏳ Atraso: <span class="text-amber-600">${c.dias_atraso} días</span></div>
                    <div>📊 Estado: <span class="text-slate-800 uppercase">${(c.estado_actual || 'pendiente').replace('_', ' ')}</span></div>
                </div>
            </div>`).join('')}
            </div>`;
        }

        if (tipo === 'diario') {
            html = `<h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">📅 Resumen Diario</h3>
            <div class="overflow-x-auto custom-scroll"><table class="w-full text-sm">
                <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-widest"><tr>
                    <th class="px-6 py-4 text-left">Fecha</th><th class="px-6 py-4 text-center">Gestiones</th>
                    <th class="px-6 py-4 text-center">Operadores</th><th class="px-6 py-4 text-center">Promesas</th>
                    <th class="px-6 py-4 text-center">Al Día</th><th class="px-6 py-4 text-center">No Responde</th>
                    <th class="px-6 py-4 text-center">Otros</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                ${datos.map(d => {
                    const otros = parseInt(d.no_corresponde||0)+parseInt(d.llamar||0)+parseInt(d.numero_baja||0)+parseInt(d.carta||0)+parseInt(d.otro||0);
                    return `<tr class="hover:bg-blue-50/30">
                        <td class="px-6 py-4 font-black text-slate-800">${d.fecha.split('-').reverse().join('/')}</td>
                        <td class="px-6 py-4 text-center font-black text-blue-600">${d.total_gestiones}</td>
                        <td class="px-6 py-4 text-center font-bold text-slate-600">${d.operadores_activos}</td>
                        <td class="px-6 py-4 text-center font-bold text-emerald-600">${d.promesas||0}</td>
                        <td class="px-6 py-4 text-center font-bold text-rose-600">${d.al_dia||0}</td>
                        <td class="px-6 py-4 text-center font-bold text-amber-600">${d.no_responde||0}</td>
                        <td class="px-6 py-4 text-center font-bold text-slate-600">${otros}</td>
                    </tr>`;
                }).join('')}
                </tbody>
            </table></div>`;
        }

        document.getElementById('rep-contenido').innerHTML = html;
    }

    function exportarReporte(formato) {
        if (!datosReporteActual || (Array.isArray(datosReporteActual) && datosReporteActual.length === 0)) {
            alert('Cargá un reporte primero.');
            return;
        }
        const nombreArchivo = `reporte_${reporteActual}_${new Date().toISOString().split('T')[0]}`;
        if (formato === 'json') {
            const blob = new Blob([JSON.stringify(datosReporteActual, null, 2)], { type: 'application/json' });
            const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `${nombreArchivo}.json`; a.click();
        }
        if (formato === 'csv') {
            if (!Array.isArray(datosReporteActual)) { alert('Este reporte no puede exportarse a CSV.'); return; }
            const headers = Object.keys(datosReporteActual[0] || {});
            let contenido = headers.join(',') + '\n';
            datosReporteActual.forEach(row => {
                contenido += headers.map(h => { let v = row[h] ?? ''; if (typeof v === 'string' && v.includes(',')) v = `"${v}"`; return v; }).join(',') + '\n';
            });
            const blob = new Blob([contenido], { type: 'text/csv;charset=utf-8;' });
            const a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = `${nombreArchivo}.csv`; a.click();
        }
    }



    // ─────────────────────────────────────────────────────────────────
    // FIN MÓDULO REPORTES
    // ─────────────────────────────────────────────────────────────────
    // ═══════════════════════════════════════════════════════════════════════════
    // MÓDULO: MI RENDIMIENTO Y AGENDA
    // ═══════════════════════════════════════════════════════════════════════════
    async function cargarMiRendimiento() {
        try {
            // Rendimiento principal
            const rRes = await fetch('api_mi_rendimiento.php?action=rendimiento');
            const rData = await rRes.json();
            if (rData.success) {
                document.getElementById('rm-asignados').innerText = rData.total_asignados;
                document.getElementById('rm-aldia').innerText = rData.al_dia;
                document.getElementById('rm-promesas').innerText = rData.promesas_activas;
                document.getElementById('rm-vencidas').innerText = rData.promesas_vencidas;
                document.getElementById('rm-posicion').innerText = '#' + rData.mi_posicion;
                document.getElementById('rm-total-ops').innerText = 'de ' + rData.total_operadores;
                
                if (rData.mi_posicion === 1) {
                    document.getElementById('rm-distancia').innerText = "¡Sos el Líder! 🥇";
                    document.getElementById('rm-barra').style.width = "100%";
                } else {
                    document.getElementById('rm-distancia').innerText = `A ${rData.diferencia_lider} gest. de ${rData.nombre_lider}`;
                    const pct = rData.max_gestiones_mes > 0 ? (rData.mi_gestiones_mes / rData.max_gestiones_mes) * 100 : 0;
                    document.getElementById('rm-barra').style.width = pct + "%";
                }
            }

            // Ranking
            const kRes = await fetch('api_mi_rendimiento.php?action=ranking_publico');
            const kData = await kRes.json();
            if (kData.success) {
                const fireIcon = 'https://lh3.googleusercontent.com/aida-public/AB6AXuAtKXPJby7U2N3SSNfK0bGc9JWlvH6LFkh_hkaU0n31Arph9hzdFP0-waShN85EI1BUYHDa_oG5xAO6kP9tnUKiOmsjpMPiMAT-VxdbkL-B092CFTkBIGWEDCuIyY7RJ4EVGC9_KAmiDbhJ6jSfun4CYV__66gBuZZwVaeWtDzDovIQ-4qDVmymWExWt1o_wPpTwGN-1-6jzyqgYlTYbI2ijusNn7dTWmobLC06P52HZYuSUdwKwO3wXm3So57bBAa4Xinn7CLsL3R5';
                const html = kData.data.map((u, i) => {
                    const selfClass = u.es_yo ? 'border-l-4 border-orange-400' : '';
                    const nameColor = u.es_yo ? 'text-crm-blue' : 'text-gray-800';
                    return `
                        <div class="bg-white p-4 rounded-xl custom-shadow flex items-center justify-between ${selfClass}">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg font-extrabold text-gray-800">#${i + 1}</span>
                                <div>
                                    <p class="text-[13px] font-bold ${nameColor}">${u.es_yo ? '🏆 ' : ''}${u.nombre}</p>
                                    <p class="text-[9px] text-gray-400 uppercase font-black">${u.es_yo ? '¡ESE SOS VOS!' : 'OPERADOR'}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="flex items-center justify-end space-x-1">
                                    <img alt="Fire" class="w-4 h-4" src="${fireIcon}"/>
                                    <span class="text-sm font-extrabold">${u.gestiones}</span>
                                </div>
                                <p class="text-[9px] font-bold text-gray-500 uppercase">Gestiones</p>
                            </div>
                        </div>
                    `;
                }).join('');
                document.getElementById('rm-ranking-body').innerHTML = html;
            }

            // Agenda Diaria
            const aRes = await fetch('api_mi_rendimiento.php?action=agenda');
            const aData = await aRes.json();
            if (aData.success) {
                if (aData.count === 0) {
                    document.getElementById('rm-agenda-body').innerHTML = `<div class="p-10 text-center text-[10px] font-black text-slate-400 uppercase">Agenda al día. No hay pendientes por hoy. 🎉</div>`;
                    // Limpiar notificaciones
                    document.getElementById('noti-badge').classList.add('hidden');
                } else {
                    const html = aData.data.map(d => {
                        let colorPri = ''; let alertPri = '';
                        if (d.tipo_agenda === 'vencida') { colorPri = 'bg-red-500'; alertPri = 'VENCIDA'; }
                        else if (d.tipo_agenda === 'hoy') { colorPri = 'bg-amber-500'; alertPri = 'HOY'; }
                        else if (d.tipo_agenda === 'llamar') { colorPri = 'bg-blue-500'; alertPri = 'LLAMAR'; }
                        
                        return `
                        <div class="p-4 flex flex-col sm:flex-row items-center justify-between hover:bg-gray-50 transition-colors gap-4">
                            <div class="flex items-center space-x-4 sm:space-x-6 flex-1 w-full">
                                <span class="${colorPri} text-white text-[10px] font-bold px-2 py-1 rounded min-w-[65px] text-center">${alertPri}</span>
                                <span class="text-sm font-bold text-gray-700 flex-1 truncate max-w-[200px]">${d.razon_social}</span>
                                <span class="text-sm font-extrabold text-crm-red whitespace-nowrap">$${parseFloat(d.total_vencido).toLocaleString('es-AR')}</span>
                                <span class="text-sm font-medium text-gray-600 hidden md:inline whitespace-nowrap">${(d.fecha_promesa && d.fecha_promesa.includes('-')) ? d.fecha_promesa.split('-').reverse().join('/') : '-'}</span>
                            </div>
                            <button onclick="editarGestionLegajo('${d.legajo}')" class="bg-[#0284c7] text-white text-[11px] font-bold px-5 py-2 rounded-lg hover:bg-blue-600 uppercase transition-colors w-full sm:w-auto shadow-sm">Gestionar</button>
                        </div>`;
                    }).join('');
                    document.getElementById('rm-agenda-body').innerHTML = html;
                    
                    // Actualizar campanita (notificaciones)
                    document.getElementById('noti-badge').classList.remove('hidden');
                    document.getElementById('noti-badge').innerText = aData.count;
                    document.getElementById('noti-count-text').innerText = `${aData.count} TAREAS`;
                    
                    const notiHtml = aData.data.slice(0, 10).map(d => {
                        const safeFecha = (d.fecha_promesa && d.fecha_promesa.includes('-')) ? d.fecha_promesa.split('-').reverse().join('/') : 'Sin fecha';
                        return `
                        <div class="px-5 py-3 hover:bg-slate-50 cursor-pointer transition border-l-4 ${d.tipo_agenda==='vencida'?'border-l-rose-500':(d.tipo_agenda==='hoy'?'border-l-amber-500':'border-l-blue-500')}" onclick="editarGestionLegajo('${d.legajo}')">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">${d.legajo}</p>
                            <p class="font-bold text-slate-700 text-xs truncate">${d.razon_social}</p>
                            <p class="text-[9px] font-bold text-rose-500 truncate mt-1">Gst: $${parseFloat(d.monto_promesa||0).toLocaleString('es-AR')} - ${safeFecha}</p>
                        </div>
                    `}).join('') + `<div class="px-5 py-4 text-center bg-slate-50"><button onclick="switchTab('rendimiento')" class="text-[9px] font-black uppercase tracking-widest text-blue-600 hover:underline">Ver agenda completa</button></div>`;
                    
                    document.getElementById('noti-list').innerHTML = notiHtml;
                }
            }

        } catch (e) {
            console.error("Error cargarMiRendimiento: ", e);
        }
    }
    
    // Función auxiliar para abrir la gestión desde la agenda / notificaciones
    async function editarGestionLegajo(legajo) {
        if(document.getElementById('noti-dropdown')) {
            document.getElementById('noti-dropdown').classList.add('hidden');
        }
        
        // Simplemente aprovechamos la función que ya existe y que está súper probada,
        // esto abre el modal sobre la misma pestaña sin hacer recargas pesadas.
        await abrirClientePorLegajo(legajo);
    }
    
    // Auto-cargar Mi Rendimiento al inicio usando un retraso leve
    setTimeout(() => {
        cargarMiRendimiento();
    }, 1000);

</script>
<?php endif; ?>
</body>
</html>