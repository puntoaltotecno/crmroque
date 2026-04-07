<?php 
/**
 * ARCHIVO: index.php
 */
require_once 'db.php'; 

if (isset($_GET['logout'])) { session_unset(); session_destroy(); header("Location: index.php"); exit; }

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .tab-active { color: #2563eb; border-bottom: 3px solid #2563eb; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
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
    <header class="bg-white/80 backdrop-blur-md border-b sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-8 py-5 flex justify-between items-center">
            <div class="flex items-center gap-10">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 w-10 h-10 rounded-xl flex items-center justify-center text-white font-black italic">R</div>
                    <h2 class="text-xl font-extrabold text-slate-800 italic tracking-tighter uppercase">CRM.ROQUE</h2>
                </div>
                <nav class="flex gap-8 border-b-0 hidden md:flex">
                    <?php if($can_assign): ?>
                    <button onclick="switchTab('dashboard')" id="btn-dashboard" class="text-[11px] font-black uppercase tracking-widest text-slate-400 transition pb-1">Tablero</button>
                    <?php endif; ?>
                    <button onclick="switchTab('clientes')" id="btn-clientes" class="text-[11px] font-black uppercase tracking-widest text-slate-400 transition pb-1">Cartera Activa</button>
                    <?php if($can_assign): ?>
                    <button onclick="switchTab('usuarios')" id="btn-usuarios" class="text-[11px] font-black uppercase tracking-widest text-slate-400 transition pb-1">Equipo</button>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="flex items-center gap-6">
                <!-- Botón de Comunicados -->
                <?php if($can_assign): ?>
                <button onclick="abrirModalComunicado()" class="text-2xl hover:scale-110 transition origin-center" title="Lanzar Aviso a Operadores">📣</button>
                <div class="w-px h-6 bg-slate-200 hidden md:block"></div>
                <?php endif; ?>

                <div class="relative cursor-pointer flex items-center justify-center w-10 h-10 bg-slate-100 rounded-xl hover:bg-slate-200 transition" onclick="toggleNotificaciones(event)">
                    <span class="text-lg">🔔</span>
                    <span id="noti-badge" class="hidden absolute -top-1 -right-1 bg-rose-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full shadow-sm">0</span>
                    
                    <div id="noti-dropdown" class="hidden absolute top-full right-0 mt-3 w-80 bg-white rounded-3xl shadow-2xl border border-slate-100 z-50 overflow-hidden cursor-default" onclick="event.stopPropagation()">
                        <div class="bg-slate-900 px-5 py-4 flex justify-between items-center">
                            <h4 class="text-white font-black text-xs uppercase tracking-widest">Alertas de Agenda</h4>
                            <span id="noti-count-text" class="text-slate-400 text-[10px] font-bold">0</span>
                        </div>
                        <div id="noti-list" class="max-h-[60vh] overflow-y-auto custom-scroll divide-y divide-slate-100">
                            <p class="text-center py-6 text-[10px] font-black uppercase text-slate-300">Cargando...</p>
                        </div>
                    </div>
                </div>

                <div class="w-px h-6 bg-slate-200 hidden md:block"></div>
                
                <div class="flex items-center gap-4">
                    <span class="text-[11px] font-black text-slate-500 uppercase tracking-widest hidden md:block">👤 <?= htmlspecialchars($nombre_usuario) ?></span>
                    <a href="?logout=1" class="bg-rose-50 text-rose-600 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-rose-100 transition">Salir</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-8 space-y-10 pb-32">
        <div class="grid grid-cols-1 md:grid-cols-<?= $rol_usuario === 'operador' ? '2' : '4' ?> gap-6">
            <?php if($rol_usuario !== 'operador'): ?>
            <div class="bg-white p-8 rounded-[2.5rem] border shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total en Calle</p><h4 id="stat-deuda" class="text-3xl font-black text-slate-800">$0</h4></div>
            <?php endif; ?>
            <div class="bg-white p-8 rounded-[2.5rem] border shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Promesas (Activas)</p><h4 id="stat-promesas" class="text-3xl font-black text-blue-600">0</h4></div>
            <div class="bg-white p-8 rounded-[2.5rem] border-l-[10px] border-l-rose-500 shadow-sm"><p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 text-rose-500">Clientes (Lista Actual)</p><h4 id="stat-filtrados" class="text-3xl font-black text-rose-600">0</h4></div>
            <?php if($rol_usuario !== 'operador'): ?>
            <div class="bg-white p-8 rounded-[2.5rem] border-l-[10px] border-l-blue-500 shadow-sm"><p class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Total clientes BD</p><h4 id="stat-total" class="text-3xl font-black text-slate-800">0</h4></div>
            <?php endif; ?>
        </div>

        <?php if($can_assign): ?>
        <section id="sec-dashboard" class="hidden space-y-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-2xl font-black text-slate-800 uppercase italic tracking-tighter">Resumen General</h3>
                <button onclick="loadDashboard()" class="text-[10px] font-black uppercase text-blue-500 hover:text-blue-700 transition bg-blue-50 px-4 py-2 rounded-xl">↻ Actualizar Tablero</button>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-slate-900 p-6 rounded-[2rem] shadow-xl relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 opacity-10 text-7xl">👥</div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 relative z-10">Total Asignados</p>
                    <h4 id="dash-asignados" class="text-3xl font-black text-white relative z-10">0</h4>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Gestionados</p>
                    <h4 id="dash-gestionados" class="text-3xl font-black text-blue-600">0</h4>
                </div>
                <div class="bg-white p-6 rounded-[2rem] border border-slate-200 shadow-sm">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Promesas Logradas</p>
                    <h4 id="dash-promesas-totales" class="text-3xl font-black text-emerald-600">0</h4>
                </div>
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 p-6 rounded-[2rem] shadow-lg text-white">
                    <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-1">Cobertura</p>
                    <h4 id="dash-cobertura" class="text-3xl font-black text-white">0%</h4>
                </div>
            </div>

            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-4 mt-8">Estado de la Cartera (Clic para filtrar)</h3>
            <div id="dash-estados" class="grid grid-cols-3 md:grid-cols-5 lg:grid-cols-9 gap-3 mb-8">
                <p class="col-span-full text-center text-xs font-bold text-slate-400 py-4 uppercase tracking-widest">Cargando estados...</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden p-8">
                        <h3 class="text-lg font-black text-slate-800 uppercase italic tracking-tighter mb-6">Métricas por Sucursal</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Sucursal</th>
                                        <th class="px-6 py-4 text-center">Total Clientes</th>
                                        <th class="px-6 py-4 text-right">En Calle (Deuda Activa)</th>
                                    </tr>
                                </thead>
                                <tbody id="listaSucursales" class="divide-y divide-slate-100">
                                    <tr><td colspan="3" class="text-center py-6 text-slate-400 font-bold text-xs uppercase tracking-widest">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden p-8">
                        <h3 class="text-lg font-black text-slate-800 uppercase italic tracking-tighter mb-6">Rendimiento por Operador</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-widest">
                                    <tr>
                                        <th class="px-6 py-5 text-left">Operador</th>
                                        <th class="px-6 py-5 text-center">Asignados</th>
                                        <th class="px-6 py-5 text-center">Gestionados</th>
                                        <th class="px-6 py-5 text-center">Promesas Logradas</th>
                                        <th class="px-6 py-5 text-center">Efectividad</th>
                                    </tr>
                                </thead>
                                <tbody id="listaDashboard" class="divide-y divide-slate-100">
                                    <tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold text-xs uppercase tracking-widest">Cargando...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-900 rounded-[2.5rem] shadow-xl p-8 flex flex-col max-h-[850px]">
                    <div class="flex items-center gap-3 mb-6 shrink-0">
                        <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse shadow-[0_0_10px_rgba(239,68,68,0.8)]"></div>
                        <h3 class="text-lg font-black text-white uppercase italic tracking-tighter">Últimas 10 Gestiones</h3>
                    </div>
                    <div id="feedGestiones" class="flex-1 overflow-y-auto custom-scroll pr-2 space-y-3">
                        <p class="text-center text-slate-500 text-xs font-bold mt-10 uppercase tracking-widest">Cargando feed en vivo...</p>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <section id="sec-clientes" class="hidden space-y-6">
            <div class="flex flex-col lg:flex-row justify-between items-center gap-4">
                <div class="flex flex-1 flex-col md:flex-row gap-3 w-full">
                    <input type="text" id="search" placeholder="Buscar por Legajo, Razón o Doc..." class="flex-1 px-6 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-semibold outline-none shadow-sm focus:ring-4 focus:ring-blue-500/10 transition">
                    
                    <select id="filter-estado" onchange="load(); stats();" class="px-5 py-4 bg-white border border-slate-200 rounded-2xl text-xs font-black uppercase outline-none shadow-sm cursor-pointer hover:bg-slate-50">
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
                    <select id="filter-operador" onchange="load(); stats();" class="px-5 py-4 bg-white border border-slate-200 rounded-2xl text-xs font-black uppercase outline-none shadow-sm cursor-pointer hover:bg-slate-50">
                        <option value="0">Todos los Operadores</option>
                        <option value="-1">👤 Sin Asignar</option>
                    </select>
                    <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-4 shadow-sm" title="Límite de filas en pantalla">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest whitespace-nowrap"># Filas</span>
                        <input type="number" id="filter-limit" min="1" max="500" value="200" onchange="load(); stats();"
                            class="w-16 py-4 bg-transparent text-xs font-black text-slate-700 outline-none text-center">
                    </div>
                    <?php endif; ?>
                </div>

                <div class="flex gap-2 w-full lg:w-auto">
                    <button onclick="exportarExcel()" class="flex-1 lg:flex-none bg-blue-50 text-blue-600 px-6 py-4 rounded-2xl text-[11px] font-black uppercase border border-blue-100 hover:bg-blue-100 transition">Exportar</button>
                    
                    <?php if($rol_usuario === 'admin'): ?>
                    <button onclick="document.getElementById('csv').click()" class="flex-1 lg:flex-none bg-slate-900 text-white px-8 py-4 rounded-2xl text-[11px] font-black uppercase shadow-xl hover:bg-slate-800 transition">Importar</button>
                    <input type="file" id="csv" class="hidden" accept=".csv" onchange="subirCSV(this)">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden relative">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-widest">
                            <tr>
                                <?php if($can_assign): ?>
                                <th class="pl-8 pr-2 py-5 text-left"><input type="checkbox" onchange="toggleSelectAll(event)" class="w-4 h-4 rounded text-blue-600 bg-slate-100 border-slate-300"></th>
                                <?php endif; ?>
                                <th class="<?= $can_assign ? 'px-2' : 'px-8' ?> py-5 text-left">Legajo / Doc</th>
                                <th class="px-8 py-5 text-left">Razón Social / Sucursal</th>
                                <th class="px-8 py-5 text-center">Cuotas / Atraso</th>
                                <th class="px-8 py-5 text-right">Vencido</th>
                                <th class="px-8 py-5 text-center">Estado / Op</th>
                                <th class="px-8 py-5 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="lista" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="sec-usuarios" class="hidden space-y-8">
            <div class="flex justify-between items-center px-4">
                <h3 class="text-2xl font-black text-slate-800 uppercase italic tracking-tighter">Personal Operativo</h3>
                <?php if($rol_usuario === 'admin'): ?>
                <div class="flex gap-3">
                    <button onclick="document.getElementById('csvAsignaciones').click()" class="bg-slate-900 text-white px-6 py-3.5 rounded-2xl text-[11px] font-black uppercase shadow-xl hover:bg-slate-800 transition">Importar Asignaciones</button>
                    <input type="file" id="csvAsignaciones" class="hidden" accept=".csv" onchange="subirCSVAsignaciones(this)">
                    <button onclick="openUserModal()" class="bg-blue-600 text-white px-8 py-3.5 rounded-2xl text-[11px] font-black uppercase shadow-xl hover:bg-blue-700 transition">Nuevo Operador</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="listaPersonal"></div>
        </section>
    </main>

    <!-- BANNER FLOTANTE DE COMUNICADOS -->
    <div id="banner-comunicado" class="hidden fixed bottom-6 right-6 max-w-sm w-full bg-slate-900 border border-slate-700 shadow-[0_20px_50px_-12px_rgba(0,0,0,0.5)] rounded-3xl p-6 z-[100] transform transition-all duration-500 translate-y-10 opacity-0">
        <div class="flex justify-between items-start mb-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl animate-bounce">📣</span>
                <div>
                    <h3 class="text-white font-black uppercase tracking-widest text-xs">Nuevo Aviso</h3>
                    <p id="lbl-destinatario-banner" class="text-[9px] text-blue-400 uppercase tracking-widest font-black"></p>
                </div>
            </div>
            <button onclick="cerrarComunicado()" class="text-slate-400 hover:text-white transition text-lg bg-slate-800 w-8 h-8 rounded-full flex items-center justify-center">✕</button>
        </div>
        <p id="txt-comunicado" class="text-slate-300 text-sm font-semibold leading-relaxed whitespace-pre-wrap"></p>
        <?php if($can_assign): ?>
        <div class="mt-5 pt-4 border-t border-slate-700/50 text-right">
            <button onclick="eliminarComunicado()" class="text-[10px] font-black text-rose-400 hover:text-rose-300 uppercase tracking-widest transition">🗑️ Quitar aviso</button>
        </div>
        <?php endif; ?>
    </div>

    <?php if($can_assign): ?>
    <!-- MODAL PARA REDACTAR COMUNICADOS -->
    <div id="modalComunicado" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white p-10 rounded-[2.5rem] w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('modalComunicado').classList.replace('flex', 'hidden')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-800 text-xl font-bold">✕</button>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-2 flex items-center gap-2">📣 Lanzar Aviso</h3>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6">Aparecerá en la pantalla del usuario seleccionado.</p>
            <form id="formComunicado" onsubmit="guardarComunicado(event)" class="space-y-4">
                <select name="usuario_destino_id" id="comunicadoDestino" class="w-full px-5 py-4 bg-slate-50 border rounded-2xl outline-none font-black uppercase text-xs text-slate-700 cursor-pointer">
                    <option value="0">🌎 PARA TODOS LOS USUARIOS</option>
                </select>
                <textarea name="mensaje" rows="4" placeholder="Escribe tu mensaje aquí..." required class="w-full p-5 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium outline-none resize-none custom-scroll focus:border-blue-500 focus:bg-white transition-colors"></textarea>
                <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black uppercase text-xs mt-2 hover:bg-blue-700 transition shadow-xl">Publicar Aviso</button>
            </form>
        </div>
    </div>
    
    <div id="bulk-actions" class="hidden fixed bottom-6 left-1/2 transform -translate-x-1/2 bg-slate-900 p-4 rounded-3xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.5)] flex items-center gap-4 z-50 border border-slate-700 w-[95%] max-w-4xl overflow-x-auto custom-scroll">
        <span id="bulk-count" class="text-blue-400 font-black text-[11px] px-4 whitespace-nowrap uppercase tracking-widest">0 seleccionados</span>
        <div class="w-px h-8 bg-slate-700 shrink-0"></div>
        <select id="masivo_operador" class="bg-slate-800 text-slate-300 border border-slate-700 text-xs px-4 py-2 rounded-xl outline-none font-bold">
            <option value="">👤 Asignar a...</option>
            <option value="0">Desasignar a todos</option>
        </select>
        <button onclick="ejecutarMasivo('asignar_operador')" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-blue-500 transition shrink-0">Aplicar</button>
        <div class="w-px h-8 bg-slate-700 shrink-0"></div>
        <select id="masivo_estado" class="bg-slate-800 text-slate-300 border border-slate-700 text-xs px-4 py-2 rounded-xl outline-none font-bold">
            <option value="">Cambiar Estado...</option>
            <option value="al_dia">Al Día</option>
            <option value="promesa">Promesa de Pago</option>
            <option value="no_responde">No Responde</option>
            <option value="no_corresponde">No Corresponde</option>
            <option value="llamar">Llamar luego</option>
            <option value="numero_baja">Nro de Baja</option>
            <option value="carta">Carta</option>
            <option value="otro">Otro</option>
        </select>
        <button onclick="ejecutarMasivo('cambiar_estado')" class="bg-blue-600 text-white px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-blue-500 transition shrink-0">Aplicar</button>
        <?php if($rol_usuario === 'admin'): ?>
        <div class="w-px h-8 bg-slate-700 shrink-0"></div>
        <button onclick="ejecutarMasivo('eliminar')" class="bg-rose-500/10 text-rose-500 border border-rose-500/20 px-5 py-2.5 rounded-xl text-[10px] font-black uppercase hover:bg-rose-500 hover:text-white transition shrink-0">🗑️ Eliminar</button>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div id="modal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-md hidden items-center justify-center p-4 z-50" onclick="closeModal()">
        <div class="bg-white rounded-[3rem] w-full max-w-7xl shadow-2xl overflow-hidden flex flex-col md:flex-row h-[90vh] scale-95 transition-all duration-300" id="mContent" onclick="event.stopPropagation()">
            <div class="w-full md:w-3/5 p-10 border-r flex flex-col bg-white overflow-y-auto custom-scroll">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 id="mRazon" class="text-3xl font-black text-slate-800 uppercase tracking-tighter leading-none mb-2"></h3>
                        <div class="flex items-center gap-3 mt-1">
                            <p id="mLegajo" class="text-blue-600 font-black text-[10px] uppercase tracking-widest"></p>
                            <span id="mWarningOtroOp" class="hidden bg-amber-100 text-amber-700 text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-lg border border-amber-200"></span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span id="mSucursal" class="bg-slate-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase text-slate-500 italic"></span>
                        <?php if($can_assign): ?>
                        <select id="mAsignacion" onchange="cambiarAsignacion(this.value)" class="bg-blue-50 text-blue-700 px-4 py-2 rounded-xl text-[10px] font-black uppercase outline-none border border-blue-100 text-right appearance-none cursor-pointer">
                            <option value="">👤 Sin Asignar</option>
                        </select>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1">Vencido</p><h5 id="mTotal" class="text-lg font-black text-slate-800"></h5></div>
                    <div class="bg-rose-50 p-5 rounded-3xl border border-rose-100"><p class="text-[9px] font-black text-rose-400 uppercase mb-1">Días Atraso</p><h5 id="mDias" class="text-lg font-black text-rose-600"></h5></div>
                    <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1">Cuotas</p><h5 id="mCuotas" class="text-lg font-black text-slate-800"></h5></div>
                    <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1">Vto</p><h5 id="mVenc" class="text-lg font-black text-slate-800"></h5></div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8 text-[11px] font-bold text-slate-500 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                    <div class="space-y-3"><p class="flex gap-2">📍 <span id="mDomicilio" class="text-slate-800"></span></p><p class="flex gap-2">📞 <span id="mTelefonos" class="text-slate-800"></span></p></div>
                    <div class="space-y-3"><p>Doc: <span id="mDocumento" class="text-slate-800"></span></p><p>Último Pago: <span id="mUltimo" class="text-slate-800"></span></p></div>
                </div>

                <form id="gForm" class="space-y-4 pt-4 border-t flex-1 flex flex-col">
                    <input type="hidden" name="action" id="gAction" value="insert">
                    <input type="hidden" name="id" id="gId" value="">
                    <input type="hidden" name="legajo" id="mLegajoRaw">
                    
                    <div class="flex items-center gap-2 mb-2 hidden" id="editWarning">
                        <span class="bg-amber-100 text-amber-700 text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-lg">Estás editando una gestión existente</span>
                        <button type="button" onclick="cancelarEdicion()" class="text-rose-500 text-[10px] font-black uppercase hover:underline">Cancelar</button>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <select name="estado" id="mEst" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
                            <option value="promesa">Promesa de Pago</option>
                            <option value="al_dia" <?= $rol_usuario === 'operador' ? 'disabled class="hidden"' : '' ?>>Al Día (Sin deuda)</option>
                            <option value="no_responde">No Responde</option>
                            <option value="no_corresponde">No Corresponde</option>
                            <option value="llamar">Llamar más tarde</option>
                            <option value="numero_baja">Número dado de baja</option>
                            <option value="carta">Carta</option>
                            <option value="otro">Otro</option>
                        </select>
                        <input type="number" step="0.01" name="monto_promesa" id="mMon" placeholder="Monto Acuerdo" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold outline-none">
                        <input type="date" name="fecha_promesa" id="mFec" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold outline-none transition-colors">
                    </div>
                    <textarea name="observacion" rows="3" required class="w-full p-5 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-medium outline-none resize-none flex-1 custom-scroll" placeholder="Resumen de la conversación..."></textarea>
                    <button type="submit" id="btnSubmitGestion" class="w-full bg-blue-600 text-white py-5 rounded-3xl font-black uppercase text-xs shadow-xl hover:bg-blue-700 transition">Guardar Gestión</button>
                </form>
            </div>
            <div class="w-full md:w-2/5 bg-slate-50 p-10 flex flex-col">
                <div class="flex justify-between items-center mb-8"><h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Línea de Tiempo</h4><button onclick="closeModal()" class="text-slate-400 hover:text-slate-900 text-2xl">✕</button></div>
                <div id="mHis" class="flex-1 overflow-y-auto space-y-4 custom-scroll pr-4"></div>
            </div>
        </div>
    </div>
    
    <?php if($rol_usuario === 'admin'): ?>
    <div id="modalUser" class="fixed inset-0 bg-slate-900/70 backdrop-blur-sm hidden items-center justify-center p-4 z-50">
        <div class="bg-white p-10 rounded-[2.5rem] w-full max-w-md shadow-2xl relative">
            <button onclick="document.getElementById('modalUser').classList.replace('flex', 'hidden')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-800 text-xl font-bold">✕</button>
            <h3 class="text-xl font-black text-slate-800 uppercase italic tracking-tighter mb-6">Personal</h3>
            <form id="userForm" class="space-y-4">
                <input type="hidden" name="id" id="uId">
                <input type="text" name="nombre" id="uNom" placeholder="Nombre completo" required class="w-full px-5 py-4 bg-slate-50 border rounded-2xl outline-none font-semibold text-sm">
                <input type="email" name="usuario" id="uMail" placeholder="Email (Usuario de login)" required class="w-full px-5 py-4 bg-slate-50 border rounded-2xl outline-none font-semibold text-sm">
                <input type="password" name="clave" id="uPass" placeholder="Contraseña (dejar vacío si no cambia)" class="w-full px-5 py-4 bg-slate-50 border rounded-2xl outline-none font-semibold text-sm">
                <select name="rol" id="uRol" class="w-full px-5 py-4 bg-slate-50 border rounded-2xl outline-none font-semibold text-sm text-slate-600">
                    <option value="operador">Operador Base</option>
                    <option value="colaborador">Colaborador (Coordinador)</option>
                    <option value="admin">Administrador Total</option>
                </select>
                <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black uppercase text-xs mt-2 hover:bg-slate-800 transition">Guardar</button>
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
            const res = await fetch('api_comunicados.php?action=get');
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

    function abrirNotificacion(e, index) { e.stopPropagation(); cerrarDropdownNoti(); openModal(notificacionesData[index]); }

    function exportarExcel() { 
        const q = document.getElementById('search').value;
        const est = document.getElementById('filter-estado').value;
        const op = document.getElementById('filter-operador')?.value || 0;
        let url = `exportar_csv.php?q=${encodeURIComponent(q)}&estado=${encodeURIComponent(est)}&operador_id=${encodeURIComponent(op)}`;
        window.location.href = url;
    }

    const loadDashboard = async () => {
        if (!canAssign) return;
        try {
            document.getElementById('listaDashboard').innerHTML = `<tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold text-xs uppercase tracking-widest">Cargando métricas...</td></tr>`;
            document.getElementById('listaSucursales').innerHTML = `<tr><td colspan="3" class="text-center py-6 text-slate-400 font-bold text-xs uppercase tracking-widest">Cargando...</td></tr>`;
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
                        return `<div onclick="aplicarFiltroDashboard('${est.id}')" class="cursor-pointer bg-white p-5 rounded-3xl border border-slate-200 shadow-sm flex flex-col items-center justify-center text-center hover:shadow-lg hover:border-blue-400 transition transform hover:-translate-y-1">
                            <h4 class="text-3xl font-black text-slate-800 mb-3">${count}</h4>
                            <span class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase ${est.class} w-full truncate border border-transparent">${est.label}</span>
                        </div>`;
                    }).join('');
                }

                if (document.getElementById('listaSucursales') && data.sucursales) {
                    document.getElementById('listaSucursales').innerHTML = data.sucursales.map(s => {
                        let monto = `$${parseFloat(s.deuda_en_calle || 0).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                        return `<tr class="hover:bg-blue-50/50 transition border-b border-slate-50">
                            <td class="px-6 py-4 font-black text-slate-800 uppercase text-[11px] tracking-widest">${s.sucursal_nombre}</td>
                            <td class="px-6 py-4 text-center font-bold text-slate-500">${s.total_clientes}</td>
                            <td class="px-6 py-4 text-right font-black text-rose-600 bg-rose-50/30">${monto}</td>
                        </tr>`;
                    }).join('') || `<tr><td colspan="3" class="text-center py-6 text-slate-400 font-bold text-xs uppercase tracking-widest">Sin datos</td></tr>`;
                }

                if (document.getElementById('listaDashboard') && data.data) {
                    document.getElementById('listaDashboard').innerHTML = data.data.map(op => {
                        const asignados = parseInt(op.total_asignados) || 0;
                        const gestionados = parseInt(op.clientes_gestionados) || 0;
                        const promesas = parseInt(op.promesas_logradas) || 0;
                        const efectividad = gestionados > 0 ? Math.round((promesas / gestionados) * 100) : 0;
                        const colorEfectividad = efectividad >= 30 ? 'text-emerald-500' : (efectividad >= 15 ? 'text-amber-500' : 'text-rose-500');
                        
                        return `<tr class="hover:bg-blue-50/50 transition border-b border-slate-50">
                            <td class="px-6 py-4 font-black text-slate-800 text-xs">${op.nombre || 'Desconocido'}</td>
                            <td class="px-6 py-4 text-center font-bold text-slate-600">${asignados}</td>
                            <td class="px-6 py-4 text-center font-bold text-blue-600">${gestionados}</td>
                            <td class="px-6 py-4 text-center font-bold text-emerald-600">${promesas}</td>
                            <td class="px-6 py-4 text-center font-black ${colorEfectividad}">${efectividad}%</td>
                        </tr>`;
                    }).join('') || `<tr><td colspan="5" class="text-center py-10 text-slate-400 font-bold text-xs uppercase tracking-widest">No hay datos</td></tr>`;
                }

                if (document.getElementById('feedGestiones') && data.ultimas_gestiones) {
                document.getElementById('feedGestiones').innerHTML = data.ultimas_gestiones.map(g => {
                    // Protecciones para evitar fallos si el dato viene nulo
                    let estadoStr = g.feed_estado || 'sin_gestion';
                    let badge = `badge-${estadoStr}`;
                    let fechaStr = g.feed_fecha || '';
                    let fechaHora = fechaStr.length >= 16 ? fechaStr.substring(0, 16).replace(/-/g, '/') : fechaStr;
                    
                    // Protección ESTRICTA para inyección en el onclick
                    let cJson = JSON.stringify(g).replace(/'/g, "\\'").replace(/"/g, '&quot;'); 
                    
                    return `<div onclick="openModal(${cJson})" class="p-5 bg-slate-800/80 rounded-2xl border border-slate-700/50 hover:bg-slate-700 cursor-pointer transition mb-3 group">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">${fechaHora}</span>
                            <span class="px-2 py-0.5 rounded text-[8px] font-black uppercase ${badge}">${estadoStr.replace('_', ' ')}</span>
                        </div>
                        <p class="font-bold text-[13px] text-white mb-2 truncate group-hover:text-blue-400 transition-colors">${g.razon_social || 'Desconocido'}</p>
                        <p class="text-[11px] text-slate-400 line-clamp-2 leading-relaxed font-medium italic">"${g.feed_obs || 'Sin observaciones'}"</p>
                        <div class="mt-3 pt-3 border-t border-slate-700/50 flex justify-between items-center">
                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">LEG: ${g.legajo}</span>
                            <span class="text-[9px] text-blue-400 font-bold uppercase">👤 ${(g.feed_operador || 'Sistema').split(' ')[0]}</span>
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
        let url = `${api_clientes}search&q=${encodeURIComponent(q)}&estado=${est}&operador_id=${op}&limit=${limit}`;

        try {
            const res = await fetch(url);
            const data = await res.json();
            
            document.getElementById('lista').innerHTML = data.map(c => {
                let badgeClass = `badge-${c.estado_actual}`;
                let labelEstado = c.estado_actual === 'sin_gestion' ? 'PENDIENTE' : c.estado_actual.replace('_', ' ').toUpperCase();
                
                let checkHTML = canAssign ? `<td class="pl-8 pr-2 py-5 text-left" onclick="event.stopPropagation()"><input type="checkbox" value="${c.legajo}" onchange="toggleSelection(event)" ${selectedLegajos.includes(c.legajo) ? 'checked' : ''} class="chk-legajo w-4 h-4 rounded text-blue-600 bg-slate-100 border-slate-300"></td>` : '';
                let paddingLegajo = canAssign ? 'px-2' : 'px-8';

                let opBadge = canAssign ? 
                    `<select onchange="cambiarAsignacionRapida('${c.legajo}', this.value)" onclick="event.stopPropagation()" class="mt-2 w-full max-w-[120px] bg-white border border-slate-200 rounded-lg text-[9px] font-black uppercase outline-none py-1 px-1">
                        <option value="">👤 Sin Asignar</option>${operadoresList.map(o => `<option value="${o.id}" ${c.operador_id == o.id ? 'selected' : ''}>👤 ${o.nombre.split(' ')[0]}</option>`).join('')}
                    </select>` : 
                    `<p class="mt-2 text-[9px] font-black text-blue-500 uppercase tracking-widest">👤 ${c.operador_asignado?.split(' ')[0] || 'SIN ASIGNAR'}</p>`;

                let trMonto = `$${parseFloat(c.total_vencido).toLocaleString('es-AR')}`;

                let cJson = JSON.stringify(c).replace(/'/g, "\\'").replace(/"/g, '&quot;'); 

                return `<tr class="hover:bg-blue-50/50 cursor-pointer transition border-l-[6px] border-l-${c.semaforo === 'blanco' ? 'transparent' : (c.semaforo === 'rojo' ? 'rose-500' : (c.semaforo === 'amarillo' ? 'amber-400' : 'emerald-500'))}" ondblclick="openModal(${cJson})">
                    ${checkHTML}
                    <td class="${paddingLegajo} py-5"><p class="font-black text-slate-800 text-sm">${c.legajo}</p><p class="text-[9px] font-bold text-slate-400 uppercase">${c.nro_documento}</p></td>
                    <td class="px-8 py-5"><p class="font-black uppercase text-slate-700 text-sm truncate max-w-[200px]">${c.razon_social}</p><p class="text-[10px] font-bold text-blue-500 uppercase italic">${c.sucursal || 'Central'}</p></td>
                    <td class="px-8 py-5 text-center"><p class="font-bold text-slate-800 text-sm">${c.c_cuotas} Ctas</p>${c.dias_atraso > 0 ? `<p class="text-[9px] font-black text-rose-500 uppercase">${c.dias_atraso} días</p>` : ''}</td>
                    <td class="px-8 py-5 text-right"><p class="font-black text-slate-900 text-base">${trMonto}</p></td>
                    <td class="px-8 py-5 text-center"><span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase ${badgeClass}">${labelEstado}</span><div class="flex justify-center">${opBadge}</div></td>
                    <td class="px-8 py-5 text-center"><button onclick="event.stopPropagation(); openModal(${cJson})" class="bg-blue-600 text-white px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase hover:bg-blue-700 transition shadow-sm">Gestionar</button></td>
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
        
        ['clientes', 'usuarios', 'dashboard'].forEach(t => {
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
    }

    window.onload = async () => { 
        if(canAssign) { 
            const res = await fetch(api_usuarios+'?action=list'); 
            operadoresList = await res.json(); 
            const opsHTML = operadoresList.map(u => `<option value="${u.id}">👤 ${u.nombre}</option>`).join('');
            
            const fOp = document.getElementById('filter-operador');
            if(fOp) fOp.innerHTML = '<option value="0">Todos los Operadores</option><option value="-1">👤 Sin Asignar</option>' + opsHTML;
            document.getElementById('mAsignacion').innerHTML = '<option value="">👤 Sin Asignar</option>' + opsHTML; 
            const bOp = document.getElementById('masivo_operador');
            if(bOp) bOp.innerHTML += opsHTML;
            switchTab('dashboard');
        } else { switchTab('clientes'); }
        
        load(); 
        stats(); 
        loadNotificaciones();
        checkComunicado();
    };

    let searchTimeout;
    document.getElementById('search').oninput = (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => { load(); stats(); }, 400);
    };

    const stats = async () => { 
        const q = document.getElementById('search').value;
        const est = document.getElementById('filter-estado').value;
        const op = document.getElementById('filter-operador')?.value || 0;
        let url = `${api_clientes}stats&q=${encodeURIComponent(q)}&estado=${est}&operador_id=${op}`;

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
            let details = h.estado === 'promesa' ? `<div class="mt-2 flex gap-4 text-[9px] font-black uppercase text-blue-500 bg-blue-50 p-2 rounded-xl"><span>💰 $${parseFloat(h.monto_promesa).toLocaleString('es-AR')}</span><span>📅 ${h.fecha_promesa?.split('-').reverse().join('/')}</span></div>` : '';
            
            let btnEdit = '';
            let btnDel = '';
            let intentos = parseInt(h.intentos || 0);
            let isOculta = h.oculta == 1;
            
            let hJson = JSON.stringify(h).replace(/'/g, "\\'").replace(/"/g, '&quot;'); 

            if (canAssign) {
                btnEdit = `<button type="button" onclick="prepararEdicion(${hJson})" class="text-blue-500 hover:text-blue-700 transition">✏️ Editar</button>`;
                btnDel = `<button type="button" onclick='eliminarGestion(${h.id}, "${legajo}")' class="text-rose-500 hover:text-rose-700 transition">🗑️ Borrar</button>`;
            } else if (isOperador && h.usuario_id == currentUserId && h.id == maxId && intentos < 3) {
                btnEdit = `<button type="button" onclick="prepararEdicion(${hJson})" class="text-blue-500 hover:text-blue-700 transition">✏️ Editar (${intentos}/3)</button>`;
            }
            
            let actionBtns = (btnEdit || btnDel) ? `<div class="flex gap-4 text-[10px] font-black uppercase mt-3 pt-3 border-t border-slate-100">${btnEdit} ${btnDel}</div>` : '';
            let ocultaBadge = isOculta ? `<span class="bg-rose-100 text-rose-600 px-2 py-0.5 rounded text-[8px] font-black uppercase ml-2">👁️ Oculta</span>` : '';

            return `<div class="bg-white p-5 rounded-3xl shadow-sm text-xs border ${isOculta ? 'border-rose-300 opacity-75' : 'border-slate-100'} mb-4 transition hover:shadow-md">
                <div class="flex justify-between items-center font-black text-[9px] mb-3 uppercase tracking-widest text-slate-400"><span>📅 ${fH}</span><span>Op: ${h.operador}</span></div>
                <div class="mb-3"><span class="px-3 py-1 rounded-full text-[8px] font-black uppercase ${badgeH}">${h.estado.replace('_', ' ')}</span> ${ocultaBadge}</div>
                <p class="text-slate-600 font-semibold leading-relaxed">${h.observacion}</p>${details}
                ${actionBtns}
            </div>`;
        }).join('') || '<p class="text-center text-slate-300 py-10 font-black tracking-widest uppercase text-[10px]">Sin gestiones previas</p>';
    }

    async function openModal(c) {
        cancelarEdicion();
        document.getElementById('mHis').innerHTML = '<p class="text-center py-10 opacity-30 text-[10px] font-black uppercase tracking-widest">Cargando...</p>';
        document.getElementById('mLegajoRaw').value = c.legajo; 
        document.getElementById('mRazon').innerText = c.razon_social; 
        document.getElementById('mLegajo').innerText = `ID: ${c.l_entidad_id || '-'} • Legajo: ${c.legajo}`;
        document.getElementById('mSucursal').innerText = c.sucursal || 'Central';
        if (canAssign) document.getElementById('mAsignacion').value = c.operador_id || '';
        
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
        document.getElementById('mTelefonos').innerText = c.telefonos || '-';
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

        let verOcultasHTML = canAssign ? `<div class="mb-4 flex justify-between items-center bg-slate-100 px-4 py-3 rounded-2xl"><span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Ver gestiones ocultas</span><input type="checkbox" id="chkVerOcultas" onchange="cargarHistorial('${c.legajo}')" class="w-4 h-4 rounded text-blue-600"></div>` : '';
        document.getElementById('mHis').innerHTML = verOcultasHTML + `<div id="mHisList"></div>`;
        
        await cargarHistorial(c.legajo);

        document.getElementById('modal').classList.replace('hidden', 'flex'); 
        setTimeout(() => document.getElementById('mContent').classList.replace('scale-95', 'scale-100'), 10);
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
        if (!confirm('¿Estás seguro de ELIMINAR permanentemente esta gestión?')) return;
        let fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        await fetch(api_gestion, { method:'POST', body:fd });
        await cargarHistorial(legajo);
        load(); stats(); if(canAssign) loadDashboard(); loadNotificaciones();
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
                    cancelarEdicion();
                    await cargarHistorial(document.getElementById('mLegajoRaw').value);
                    load(); 
                    stats(); 
                    loadNotificaciones();
                    if(canAssign) loadDashboard(); 
                } else { alert("Error: " + d.message); }
            } catch(err) { alert("Error de conexión al guardar."); } 
            finally { btn.disabled = false; btn.innerText = textOrig; }
        };
    }

    async function cambiarAsignacion(uid) { const legajo = document.getElementById('mLegajoRaw').value; if(!legajo) return; const fd = new FormData(); fd.append('legajo', legajo); fd.append('usuario_id', uid); await fetch(api_clientes+'assign', {method:'POST', body:fd}); load(); stats(); if(canAssign) loadDashboard(); }
    async function cambiarAsignacionRapida(legajo, uid) { const fd = new FormData(); fd.append('legajo', legajo); fd.append('usuario_id', uid); await fetch(api_clientes+'assign', {method:'POST', body:fd}); stats(); if(canAssign) loadDashboard(); }
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
                const msg = d.count + (d.errores && d.errores.length ? '\\n\\nErrores:\\n' + d.errores.join('\\n') : '');
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
</script>
<?php endif; ?>
</body>
</html>