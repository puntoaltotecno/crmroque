<?php 
/**
 * ARCHIVO: index.php
 */
require_once 'db.php'; 

if (isset($_GET['logout'])) { session_unset(); session_destroy(); header("Location: index.php"); exit; }

$nombre_usuario = $_SESSION['user_name'] ?? 'Usuario';
$rol_usuario    = $_SESSION['user_rol'] ?? 'operador';
$user_id        = $_SESSION['user_id'] ?? 0;
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
        .semaforo-rojo { border-left: 8px solid #ef4444; }
        .semaforo-amarillo { border-left: 8px solid #f59e0b; }
        .semaforo-verde { border-left: 8px solid #10b981; }
        .custom-scroll::-webkit-scrollbar { width: 6px; }
        .custom-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .tab-active { color: #2563eb; border-bottom: 3px solid #2563eb; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">

    <?php if (!$user_id): ?>
    <!-- LOGIN -->
    <div class="flex items-center justify-center min-h-screen p-6 bg-slate-100">
        <div class="bg-white p-12 rounded-[3.5rem] shadow-2xl w-full max-w-sm border border-slate-200 text-center">
            <div class="bg-blue-600 w-24 h-24 rounded-[2rem] mx-auto mb-4 flex items-center justify-center shadow-2xl shadow-blue-200"><span class="text-white text-5xl font-extrabold italic">R</span></div>
            <h1 class="text-3xl font-extrabold text-slate-800 tracking-tighter uppercase mb-10">CRM Roque</h1>
            <form id="loginForm" class="space-y-6">
                <input type="text" name="usuario" placeholder="Email" required class="w-full px-7 py-4 bg-slate-50 border rounded-3xl outline-none font-semibold">
                <input type="password" name="clave" placeholder="Contraseña" required class="w-full px-7 py-4 bg-slate-50 border rounded-3xl outline-none font-semibold">
                <button type="submit" class="w-full bg-blue-600 text-white py-5 rounded-3xl font-extrabold uppercase shadow-2xl">Entrar</button>
            </form>
            <div id="lError" class="mt-6 text-red-600 text-xs font-bold hidden bg-red-50 py-4 rounded-2xl"></div>
        </div>
    </div>

    <?php else: ?>
    <!-- DASHBOARD -->
    <header class="bg-white/80 backdrop-blur-md border-b sticky top-0 z-40 shadow-sm">
        <div class="max-w-7xl mx-auto px-8 py-5 flex justify-between items-center">
            <div class="flex items-center gap-10">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-600 w-10 h-10 rounded-xl flex items-center justify-center text-white font-black italic">R</div>
                    <h2 class="text-2xl font-extrabold text-slate-800 italic tracking-tighter uppercase">CRM.ROQUE</h2>
                </div>
                <nav class="flex gap-8">
                    <button onclick="switchTab('clientes')" id="btn-clientes" class="text-xs font-black uppercase tracking-widest transition tab-active">Cartera Activa</button>
                    <?php if($rol_usuario === 'admin'): ?>
                    <button onclick="switchTab('usuarios')" id="btn-usuarios" class="text-xs font-black uppercase tracking-widest text-slate-400 transition">Equipo</button>
                    <?php endif; ?>
                </nav>
            </div>
            <div class="flex items-center gap-4">
                <span class="text-xs font-bold text-slate-500 uppercase">👤 <?= $nombre_usuario ?></span>
                <a href="?logout=1" class="bg-rose-50 text-rose-600 px-6 py-3 rounded-2xl text-[10px] font-black uppercase hover:bg-rose-100 transition">Salir</a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-8 space-y-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-10 rounded-[2.5rem] border shadow-sm"><p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Total en la Calle</p><h4 id="stat-deuda" class="text-4xl font-extrabold text-slate-800">$0</h4></div>
            <div class="bg-white p-10 rounded-[2.5rem] border shadow-sm"><p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2">Promesas Activas</p><h4 id="stat-promesas" class="text-4xl font-extrabold text-blue-600">0</h4></div>
            <div class="bg-white p-10 rounded-[2.5rem] border-l-[12px] border-l-rose-500 shadow-sm"><p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 text-rose-500">Clientes en Mora</p><h4 id="stat-mora" class="text-4xl font-extrabold text-rose-600">0</h4></div>
        </div>

        <section id="sec-clientes" class="space-y-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 flex-wrap">
                <input type="text" id="search" placeholder="Buscar por Legajo, Razón Social o Documento..." class="w-full md:w-[26rem] pl-8 pr-8 py-5 bg-white border border-slate-200 rounded-[2rem] text-sm font-semibold outline-none shadow-sm focus:ring-4 focus:ring-blue-500/10 transition">
                <div class="flex gap-3 flex-wrap items-center">
                    <select id="filtroEstado" onchange="loadFiltros()" class="px-5 py-4 bg-white border border-slate-200 rounded-[2rem] text-xs font-black uppercase outline-none shadow-sm cursor-pointer text-slate-600">
                        <option value="">Todos los estados</option>
                        <option value="promesa">Promesa</option>
                        <option value="no_responde">No responde</option>
                        <option value="no_corresponde">No corresponde</option>
                        <option value="llamar">Llamar</option>
                        <option value="numero_baja">Número baja</option>
                        <option value="otro">Otro</option>
                    </select>
                    <?php if($rol_usuario === 'admin'): ?>
                    <select id="filtroOperador" onchange="loadFiltros()" class="px-5 py-4 bg-white border border-slate-200 rounded-[2rem] text-xs font-black uppercase outline-none shadow-sm cursor-pointer text-slate-600">
                        <option value="">Todos los operadores</option>
                    </select>
                    <?php endif; ?>
                    <button onclick="exportarExcel()" class="bg-blue-50 text-blue-600 px-8 py-5 rounded-[2rem] text-xs font-black uppercase border border-blue-100">Exportar</button>
                    <?php if($rol_usuario === 'admin'): ?>
                    <button onclick="document.getElementById('csv').click()" class="bg-slate-900 text-white px-10 py-5 rounded-[2rem] text-xs font-black uppercase shadow-2xl hover:bg-slate-800 transition">Importar</button>
                    <input type="file" id="csv" class="hidden" accept=".csv" onchange="subirCSV(this)">
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- BARRA ACCIONES MASIVAS (solo admin, aparece al seleccionar) -->
            <?php if($rol_usuario === 'admin'): ?>
            <div id="barraMasiva" class="hidden bg-blue-600 text-white px-8 py-4 rounded-[2rem] flex items-center gap-6 flex-wrap shadow-xl">
                <span id="countSeleccionados" class="text-xs font-black uppercase tracking-widest">0 seleccionados</span>
                <div class="flex gap-3 ml-auto flex-wrap items-center">
                    <select id="masivo_operador" class="px-4 py-2 rounded-xl text-xs font-black text-slate-800 outline-none cursor-pointer">
                        <option value="">👤 Sin Asignar</option>
                    </select>
                    <button onclick="accionMasiva('asignar_operador')" class="bg-white text-blue-600 px-5 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-blue-50 transition">Asignar Op.</button>
                    <select id="masivo_estado" class="px-4 py-2 rounded-xl text-xs font-black text-slate-800 outline-none cursor-pointer">
                        <option value="promesa">Promesa</option>
                        <option value="no_responde">No responde</option>
                        <option value="no_corresponde">No corresponde</option>
                        <option value="llamar">Llamar</option>
                        <option value="numero_baja">Número baja</option>
                        <option value="al_dia">Al día</option>
                        <option value="otro">Otro</option>
                    </select>
                    <button onclick="accionMasiva('cambiar_estado')" class="bg-white text-blue-600 px-5 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-blue-50 transition">Cambiar Estado</button>
                    <button onclick="accionMasiva('eliminar')" class="bg-rose-500 text-white px-5 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-rose-600 transition">🗑 Eliminar</button>
                    <button onclick="deseleccionarTodos()" class="bg-blue-500 text-white px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-blue-400 transition">✕</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white rounded-[3rem] shadow-sm border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 border-b text-slate-400 font-black uppercase text-[10px] tracking-[0.2em]">
                            <tr>
                                <?php if($rol_usuario === 'admin'): ?>
                                <th class="px-4 py-6 text-center w-10">
                                    <input type="checkbox" id="chkTodos" onchange="seleccionarTodos(this)" class="w-4 h-4 rounded accent-blue-600 cursor-pointer">
                                </th>
                                <?php endif; ?>
                                <th class="px-8 py-6 text-left">Legajo / Doc</th>
                                <th class="px-8 py-6 text-left">Razón Social / Sucursal</th>
                                <th class="px-8 py-6 text-center">Cuotas / Días</th>
                                <th class="px-8 py-6 text-right">Vencido</th>
                                <th class="px-8 py-6 text-center">Estado / Op</th>
                                <th class="px-8 py-6 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="lista" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>
        </section>

        <section id="sec-usuarios" class="hidden space-y-8">
            <div class="flex justify-between items-center px-4">
                <h3 class="text-3xl font-extrabold text-slate-800 uppercase italic">Personal Operativo</h3>
                <div class="flex gap-2">
                    <button onclick="document.getElementById('csvAsignaciones').click()" class="bg-slate-900 text-white px-6 py-4 rounded-[2rem] text-xs font-black uppercase shadow-xl hover:bg-slate-800 transition">Asignar Cartera (CSV)</button>
                    <input type="file" id="csvAsignaciones" class="hidden" accept=".csv" onchange="subirCSVAsignaciones(this)">
                    <button onclick="openUserModal()" class="bg-blue-600 text-white px-10 py-4 rounded-[2rem] text-xs font-black uppercase shadow-xl hover:bg-blue-700 transition">Nuevo Operador</button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8" id="listaPersonal"></div>
        </section>
    </main>

    <!-- MODAL 360 -->
    <div id="modal" class="fixed inset-0 bg-slate-900/70 backdrop-blur-md hidden items-center justify-center p-4 z-50">
        <div class="bg-white rounded-[3.5rem] w-full max-w-7xl shadow-2xl overflow-hidden flex flex-col md:flex-row h-[92vh] scale-95 transition-all duration-300" id="mContent">
            
            <div class="w-full md:w-3/5 p-10 lg:p-14 border-r flex flex-col bg-white overflow-y-auto custom-scroll">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 id="mRazon" class="text-3xl lg:text-4xl font-extrabold text-slate-800 uppercase tracking-tighter leading-tight"></h3>
                        <p id="mLegajo" class="text-blue-600 font-black text-[10px] uppercase tracking-widest mt-2"></p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span id="mSucursal" class="bg-slate-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase text-slate-500 whitespace-nowrap"></span>
                        <?php if($rol_usuario === 'admin'): ?>
                        <select id="mAsignacion" onchange="cambiarAsignacion(this.value)" class="bg-blue-50 text-blue-700 px-4 py-2 rounded-xl text-[10px] font-black uppercase outline-none cursor-pointer border border-blue-100 shadow-sm transition hover:bg-blue-100 appearance-none text-right">
                            <option value="">👤 Sin Asignar</option>
                        </select>
                        <?php else: ?>
                        <span id="mAsignacionOp" class="bg-slate-50 text-slate-400 px-4 py-2 rounded-xl text-[9px] font-black uppercase border border-slate-100"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1">Vencido</p><h5 id="mTotal" class="text-xl font-black text-slate-800 tracking-tighter"></h5></div>
                    <div class="bg-amber-50 p-5 rounded-3xl border border-amber-100"><p class="text-[9px] font-black text-amber-500 uppercase mb-1">Mora</p><h5 id="mMora" class="text-xl font-black text-amber-600 tracking-tighter"></h5></div>
                    <div class="bg-rose-50 p-5 rounded-3xl border border-rose-100"><p class="text-[9px] font-black text-rose-400 uppercase mb-1">Días</p><h5 id="mDias" class="text-xl font-black text-rose-600 tracking-tighter"></h5></div>
                    <div class="bg-slate-50 p-5 rounded-3xl border border-slate-100"><p class="text-[9px] font-black text-slate-400 uppercase mb-1">Cuotas</p><h5 id="mCuotas" class="text-xl font-black text-slate-800 tracking-tighter"></h5></div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8 text-[11px] font-bold text-slate-500 bg-slate-50 p-6 rounded-3xl">
                    <div class="space-y-3"><p class="flex gap-2"><span class="opacity-50">📍</span> <span id="mDomicilio" class="text-slate-800"></span></p><p class="flex gap-2"><span class="opacity-50">📞</span> <span id="mTelefonos" class="text-slate-800"></span></p></div>
                    <div class="space-y-3"><p>DNI / CUIT: <span id="mDocumento" class="text-slate-800"></span></p><p>Último Pago: <span id="mUltimo" class="text-slate-800"></span></p><p>Vencimiento: <span id="mVenc" class="text-rose-600"></span></p></div>
                </div>

                <form id="gForm" class="space-y-4 pt-4 border-t flex-1 flex flex-col">
                    <input type="hidden" name="cliente_id" id="mId">
                    <input type="hidden" name="legajo" id="mLegajoRaw">
                    <div class="grid grid-cols-3 gap-4">
                        <select name="estado" id="mEst" class="p-4 bg-slate-50 border rounded-2xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500"><option value="promesa">Promesa</option><option value="no_responde">No responde</option><option value="no_corresponde">No corresponde</option><option value="llamar">Llamar</option><option value="numero_baja">Número baja</option><option value="otro">Otro</option></select>
                        <input type="number" step="0.01" name="monto_promesa" id="mMon" placeholder="Monto Acuerdo" class="p-4 bg-slate-50 border rounded-2xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500">
                        <input type="date" name="fecha_promesa" id="mFec" class="p-4 bg-slate-50 border rounded-2xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <textarea name="observacion" rows="3" required class="w-full p-5 bg-slate-50 border rounded-2xl text-sm font-medium outline-none resize-none flex-1 custom-scroll" placeholder="Escribe aquí el resumen de la llamada..."></textarea>
                    <button type="submit" class="w-full bg-blue-600 text-white py-5 rounded-3xl font-black uppercase text-xs shadow-xl hover:bg-blue-700 transition">Guardar Acción de Cobro</button>
                </form>
            </div>
            
            <div class="w-full md:w-2/5 bg-slate-100 p-10 lg:p-14 flex flex-col">
                <div class="flex justify-between items-center mb-8"><h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">Línea de Tiempo</h4><button onclick="closeModal()" class="text-slate-400 hover:text-slate-900 text-2xl">✕</button></div>
                <div id="mHis" class="flex-1 overflow-y-auto space-y-4 custom-scroll pr-4"></div>
            </div>
        </div>
    </div>

    <!-- MODAL USUARIOS -->
    <div id="uModal" class="fixed inset-0 bg-slate-900/60 hidden items-center justify-center p-4 z-50">
        <div class="bg-white p-12 rounded-[3.5rem] w-full max-w-sm shadow-2xl">
            <h3 id="uModalTitle" class="text-2xl font-black mb-8 uppercase italic">Acceso de Equipo</h3>
            <form id="uForm" class="space-y-4">
                <input type="hidden" name="id" id="uid">
                <input type="text" name="nombre" placeholder="Nombre completo" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none">
                <input type="text" name="usuario" placeholder="Email" required class="w-full p-4 bg-slate-50 rounded-2xl outline-none">
                <input type="password" name="clave" id="uClave" placeholder="Contraseña" class="w-full p-4 bg-slate-50 rounded-2xl outline-none">
                <select name="rol" class="w-full p-4 bg-slate-50 rounded-2xl font-bold"><option value="operador">Operador</option><option value="admin">Administrador</option></select>
                <div class="flex gap-4 pt-6"><button type="button" onclick="document.getElementById('uModal').classList.add('hidden')" class="flex-1 bg-slate-100 py-4 rounded-2xl font-black text-[10px] uppercase">Cerrar</button><button type="submit" class="flex-1 bg-blue-600 text-white py-4 rounded-2xl font-black text-[10px] uppercase shadow-lg">Guardar</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
    const currentUserId = <?= $user_id ?>, isAdmin = <?= $rol_usuario === 'admin' ? 'true' : 'false' ?>;
    let operadoresList = [];
    const api_clientes = 'api_clientes.php?action=', api_gestion = 'api_gestion.php', api_historial = 'api_historial.php?id=', api_usuarios = 'api_usuarios.php';

    // Función principal de carga con soporte de filtros
    const load = async (q = '', estado = '', operador_id = '') => {
        try {
            let url = api_clientes+'search&q='+encodeURIComponent(q);
            if (estado)      url += '&estado='      + encodeURIComponent(estado);
            if (operador_id) url += '&operador_id=' + encodeURIComponent(operador_id);
            const res = await fetch(url), data = await res.json();
            document.getElementById('lista').innerHTML = data.map(c => {
                const total = parseFloat(c.total_vencido) + parseFloat(c.mora);
                let opBadge = '';
                if (isAdmin && c.legajo) {
                    const opts = '<option value="">👤 Sin Asignar</option>' + operadoresList.map(o => `<option value="${o.id}" ${c.operador_id == o.id ? 'selected' : ''}>👤 ${o.nombre.split(' ')[0]}</option>`).join('');
                    opBadge = `<select onchange="cambiarAsignacionRapida('${c.legajo}', this.value)" onclick="event.stopPropagation()" class="mt-2 w-full max-w-[110px] bg-white border border-slate-200 rounded-lg text-[9px] font-black uppercase outline-none cursor-pointer py-1 px-1 transition hover:bg-slate-50">${opts}</select>`;
                } else {
                    opBadge = c.operador_asignado ? `<p class="mt-2 text-[9px] font-black text-blue-500 uppercase tracking-widest">👤 ${c.operador_asignado.split(' ')[0]}</p>` : `<p class="mt-2 text-[8px] font-bold text-slate-300 uppercase tracking-widest">Sin asignar</p>`;
                }
                return `<tr class="hover:bg-blue-50/50 cursor-pointer semaforo-${c.semaforo} transition" ondblclick='openModal(${JSON.stringify(c).replace(/'/g, "\\'")})'>
                    ${isAdmin ? `<td class="px-4 py-6 text-center" onclick="event.stopPropagation()">${c.legajo ? `<input type="checkbox" class="chk-cliente w-4 h-4 rounded accent-blue-600 cursor-pointer" value="${c.legajo}" onchange="actualizarSeleccion()">` : ""}</td>` : ""}
                    <td class="px-8 py-6"><p class="font-black text-slate-800 text-sm uppercase">${c.legajo || 'S/L'}</p><p class="text-[9px] font-black text-slate-400 uppercase">${c.nro_documento}</p></td>
                    <td class="px-8 py-6"><p class="font-black uppercase text-slate-700 text-sm">${c.razon_social}</p><p class="text-[10px] font-bold text-blue-500 uppercase italic">${c.sucursal || 'Central'}</p></td>
                    <td class="px-8 py-6 text-center"><p class="font-bold text-slate-800 text-sm">${c.c_cuotas} Cuotas</p>${c.dias_atraso > 0 ? `<p class="text-[9px] font-black text-rose-500 uppercase">${c.dias_atraso} días</p>` : ''}</td>
                    <td class="px-8 py-6 text-right"><p class="font-black text-slate-900 text-base">$${total.toLocaleString('es-AR')}</p></td>
                    <td class="px-8 py-6 text-center"><span class="px-5 py-2 rounded-full text-[10px] font-black uppercase ${{'promesa':'bg-blue-100 text-blue-700','no_responde':'bg-orange-100 text-orange-700','no_corresponde':'bg-red-100 text-red-700','llamar':'bg-emerald-100 text-emerald-700','numero_baja':'bg-slate-200 text-slate-600','otro':'bg-violet-100 text-violet-700','al_dia':'bg-teal-100 text-teal-700'}[c.estado]||'bg-amber-100 text-amber-700'}">${(c.estado==='sin_gestion'?'pendiente':c.estado).replace(/_/g,' ')}</span><div class="flex justify-center">${opBadge}</div></td>
                    <td class="px-8 py-6 text-center"><button onclick='event.stopPropagation(); openModal(${JSON.stringify(c).replace(/'/g, "\\'")})' class="bg-blue-600 text-white px-5 py-2.5 rounded-2xl text-[10px] font-black uppercase hover:bg-blue-700 transition shadow-md">Gestionar</button></td>
                </tr>`;
            }).join('');
        } catch(e) { console.error(e); }
    };

    async function openModal(c) {
        // Limpiamos historial previo antes de cargar para evitar ver datos de otro cliente
        document.getElementById('mHis').innerHTML = '<p class="text-center py-10 opacity-30 text-[10px] font-black uppercase tracking-widest">Cargando...</p>';
        
        document.getElementById('mId').value = c.id; 
        document.getElementById('mLegajoRaw').value = c.legajo || ''; 
        document.getElementById('mRazon').innerText = c.razon_social; 
        document.getElementById('mLegajo').innerText = `ID: ${c.l_entidad_id || '-'} • Legajo: ${c.legajo || 'S/L'}`;
        document.getElementById('mSucursal').innerText = c.sucursal || 'Central';
        
        if (isAdmin) document.getElementById('mAsignacion').value = c.operador_id || '';
        document.getElementById('mTotal').innerText = `$${parseFloat(c.total_vencido).toLocaleString('es-AR')}`;
        document.getElementById('mMora').innerText = `$${parseFloat(c.mora).toLocaleString('es-AR')}`;
        document.getElementById('mDias').innerText = c.dias_atraso || '0'; 
        document.getElementById('mCuotas').innerText = c.c_cuotas || '0';
        document.getElementById('mDomicilio').innerText = c.domicilio || '-'; 
        document.getElementById('mTelefonos').innerText = c.telefonos || '-';
        document.getElementById('mDocumento').innerText = c.nro_documento || '-';
        document.getElementById('mUltimo').innerText = c.ultimo_pago ? c.ultimo_pago.split('-').reverse().join('/') : 'Nunca';
        document.getElementById('mVenc').innerText = c.vencimiento ? c.vencimiento.split('-').reverse().join('/') : '-';
        document.getElementById('mEst').value = c.estado; 
        document.getElementById('mMon').value = c.monto_promesa || ''; 
        document.getElementById('mFec').value = c.fecha_promesa || '';
        
        // CARGA DE HISTORIAL USANDO LEGAJO
        const hRes = await fetch(api_historial + c.id + '&legajo=' + encodeURIComponent(c.legajo || ''));
        const hData = await hRes.json();
        
        document.getElementById('mHis').innerHTML = hData.map(h => {
            let fH = 'Fecha desconocida'; 
            if(h.fecha && h.fecha.includes(' ')) { 
                const p = h.fecha.split(' '); 
                fH = `📅 ${p[0].split('-').reverse().join('/')} ${p[1]}`; 
            }
            // Badge de estado
            const estadoColors = {
                'promesa':       'bg-blue-100 text-blue-700',
                'no_responde':   'bg-orange-100 text-orange-700',
                'no_corresponde':'bg-red-100 text-red-700',
                'llamar':        'bg-emerald-100 text-emerald-700',
                'numero_baja':   'bg-slate-200 text-slate-600',
                'otro':          'bg-violet-100 text-violet-700',
                'al_dia':        'bg-teal-100 text-teal-700'
            };
            const estadoBadge = h.estado 
                ? `<span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase ${estadoColors[h.estado] || 'bg-slate-100 text-slate-600'}">${h.estado}</span>` 
                : '';
            // Monto y fecha promesa
            const montoStr = (h.monto_promesa && parseFloat(h.monto_promesa) > 0) 
                ? `<span class="text-emerald-600 font-black">$${parseFloat(h.monto_promesa).toLocaleString('es-AR')}</span>` 
                : '';
            const fechaPStr = h.fecha_promesa 
                ? `<span class="text-slate-500">📆 ${h.fecha_promesa.split('-').reverse().join('/')}</span>` 
                : '';

            return `<div class="bg-white p-5 rounded-3xl shadow-sm text-xs border border-slate-100 mb-4 transition hover:shadow-md">
                <div class="flex justify-between font-black text-[9px] text-blue-500 mb-2 uppercase tracking-widest">
                    <span>${fH}</span>
                    <span>Op: ${h.operador || 'Sistema'}</span>
                </div>
                <div class="flex items-center gap-2 mb-2 flex-wrap">
                    ${estadoBadge}
                    ${montoStr}
                    ${fechaPStr}
                </div>
                <p class="text-slate-600 font-semibold leading-relaxed">${h.observacion || 'Sin texto'}</p>
            </div>`;
        }).join('') || '<p class="text-center text-slate-300 py-10 font-black tracking-widest uppercase text-[10px]">Sin gestiones previas</p>';

        document.getElementById('modal').classList.replace('hidden', 'flex'); 
        setTimeout(() => document.getElementById('mContent').classList.replace('scale-95', 'scale-100'), 10);
    }

    async function cambiarAsignacion(uid) {
        const legajo = document.getElementById('mLegajoRaw').value; if(!legajo) return;
        const fd = new FormData(); fd.append('legajo', legajo); fd.append('usuario_id', uid);
        try { const res = await fetch(api_clientes+'assign', {method:'POST', body:fd}), d = await res.json(); if(d.success) { load(document.getElementById('search').value); stats(); } } catch(e) {}
    }

    async function cambiarAsignacionRapida(legajo, uid) {
        const fd = new FormData(); fd.append('legajo', legajo); fd.append('usuario_id', uid);
        try { const res = await fetch(api_clientes+'assign', {method:'POST', body:fd}), d = await res.json(); if(d.success) stats(); else load(document.getElementById('search').value); } catch(e) { load(document.getElementById('search').value); }
    }

    // Función que lee los filtros y llama a load
    function loadFiltros() {
        const q  = document.getElementById('search').value;
        const estado = document.getElementById('filtroEstado')?.value || '';
        const opId   = document.getElementById('filtroOperador')?.value || '';
        load(q, estado, opId);
    }

    const stats = async () => { 
        const res = await fetch(api_clientes+'stats'), d = await res.json(); 
        document.getElementById('stat-deuda').innerText = '$' + parseFloat(d.deuda).toLocaleString('es-AR');
        document.getElementById('stat-promesas').innerText = d.promesas; document.getElementById('stat-mora').innerText = d.mora;
    };

    function closeModal() { document.getElementById('mContent').classList.replace('scale-100', 'scale-95'); setTimeout(() => document.getElementById('modal').classList.replace('flex', 'hidden'), 300); }
    
    function switchTab(tab) {
        document.getElementById('sec-clientes').classList.toggle('hidden', tab !== 'clientes');
        document.getElementById('sec-usuarios').classList.toggle('hidden', tab !== 'usuarios');
        // Actualizar estilos del menú activo
        document.getElementById('btn-clientes').classList.toggle('tab-active', tab === 'clientes');
        document.getElementById('btn-clientes').classList.toggle('text-slate-400', tab !== 'clientes');
        document.getElementById('btn-usuarios').classList.toggle('tab-active', tab === 'usuarios');
        document.getElementById('btn-usuarios').classList.toggle('text-slate-400', tab !== 'usuarios');
        if(tab === 'usuarios') loadPersonal();
    }

    function openUserModal() {
        document.getElementById('uForm').reset();
        document.getElementById('uid').value = '';
        document.getElementById('uModalTitle').innerText = 'Nuevo Acceso';
        document.getElementById('uClave').required = true;
        document.getElementById('uClave').placeholder = 'Contraseña';
        document.getElementById('uModal').classList.replace('hidden', 'flex');
    }
    function editarUsuario(u) {
        document.getElementById('uForm').reset();
        document.getElementById('uid').value = u.id;
        document.getElementById('uModalTitle').innerText = 'Editar Acceso';
        document.getElementById('uForm').elements['nombre'].value = u.nombre;
        document.getElementById('uForm').elements['usuario'].value = u.usuario;
        document.getElementById('uForm').elements['rol'].value = u.rol;
        document.getElementById('uClave').required = false;
        document.getElementById('uClave').placeholder = 'Dejar vacío para no cambiar';
        document.getElementById('uModal').classList.replace('hidden', 'flex');
    }
    async function eliminarUsuario(id, nombre) {
        if(confirm(`¿Eliminar acceso de ${nombre}?`)) {
            const fd = new FormData(); fd.append('id', id);
            const res = await fetch(api_usuarios+'?action=delete', {method:'POST', body:fd});
            const d = await res.json();
            if(d.success) loadPersonal(); else alert(d.message);
        }
    }
    if(document.getElementById('uForm')) {
        document.getElementById('uForm').onsubmit = async (e) => {
            e.preventDefault();
            const res = await fetch(api_usuarios+'?action=save', {method:'POST', body:new FormData(e.target)});
            const d = await res.json();
            if(d.success) { document.getElementById('uModal').classList.replace('flex','hidden'); loadPersonal(); e.target.reset(); }
            else alert(d.message || 'Error al guardar.');
        };
    }
    
    if(document.getElementById('search')){ 
        document.getElementById('search').oninput = (e) => loadFiltros();
        window.onload = async () => { 
            if(isAdmin) { 
                try { 
                    const res = await fetch(api_usuarios+'?action=list'), data = await res.json(); 
                    operadoresList = data; 
                    // Llenar select del modal
                    document.getElementById('mAsignacion').innerHTML = '<option value="">👤 Sin Asignar</option>' + data.map(u => `<option value="${u.id}">👤 ${u.nombre.split(' ')[0]}</option>`).join('');
                    // Llenar filtro de operadores
                    const filtroOp = document.getElementById('filtroOperador');
                    if (filtroOp) {
                        filtroOp.innerHTML = '<option value="">Todos los operadores</option>' + data.map(u => `<option value="${u.id}">${u.nombre.split(' ')[0]}</option>`).join('');
                    }
                    // Llenar select de operadores en barra masiva
                    const masivoOp = document.getElementById('masivo_operador');
                    if (masivoOp) {
                        masivoOp.innerHTML = '<option value="">👤 Sin Asignar</option>' + data.map(u => `<option value="${u.id}">${u.nombre.split(' ')[0]}</option>`).join('');
                    }
                } catch(e) {} 
            }
            load(); stats(); 
        }; 
    }
    
    const loadPersonal = async () => {
        const res = await fetch(api_usuarios+'?action=list'), data = await res.json();
        document.getElementById('listaPersonal').innerHTML = data.map(u => `
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col justify-between group relative">
                <div class="absolute top-6 right-6 flex gap-2"><button onclick='editarUsuario(${JSON.stringify(u)})' class="p-2 bg-blue-50 text-blue-600 rounded-xl transition hover:bg-blue-600 hover:text-white">✏️</button>${u.id != currentUserId ? `<button onclick='eliminarUsuario(${u.id}, \"${u.nombre}\")' class="p-2 bg-rose-50 text-rose-600 rounded-xl transition hover:bg-rose-600 hover:text-white">🗑️</button>` : ''}</div>
                <div class="mt-4"><p class="font-extrabold text-slate-800 text-lg uppercase tracking-tighter mb-1 pr-16">${u.nombre}</p><p class="text-[9px] font-black text-slate-400 uppercase break-all">${u.usuario}</p></div>
            </div>`).join('');
    };

    if(document.getElementById('gForm')){
        document.getElementById('gForm').onsubmit = async (e) => {
            e.preventDefault(); const btn = e.target.querySelector('button'); btn.disabled = true; btn.innerText = 'Guardando...';
            try {
                const fd = new FormData(e.target), res = await fetch(api_gestion, { method: 'POST', body: fd }), d = await res.json();
                if (d.success) {
                    const cid = fd.get('cliente_id'), legajoActual = document.getElementById('mLegajoRaw').value;
                    const hRes = await fetch(api_historial + cid + '&legajo=' + encodeURIComponent(legajoActual || '')), hData = await hRes.json();
                    const estadoColors = {'promesa':'bg-blue-100 text-blue-700','no_responde':'bg-orange-100 text-orange-700','no_corresponde':'bg-red-100 text-red-700','llamar':'bg-emerald-100 text-emerald-700','numero_baja':'bg-slate-200 text-slate-600','otro':'bg-violet-100 text-violet-700','al_dia':'bg-teal-100 text-teal-700'};
                    document.getElementById('mHis').innerHTML = hData.map(h => {
                        let fH = 'Fecha desconocida'; if(h.fecha && h.fecha.includes(' ')) { const p = h.fecha.split(' '); fH = `📅 ${p[0].split('-').reverse().join('/')} ${p[1]}`; }
                        const estadoBadge = h.estado ? `<span class="px-2 py-0.5 rounded-full text-[8px] font-black uppercase ${estadoColors[h.estado]||'bg-slate-100 text-slate-600'}">${h.estado}</span>` : '';
                        const montoStr = (h.monto_promesa && parseFloat(h.monto_promesa) > 0) ? `<span class="text-emerald-600 font-black">$${parseFloat(h.monto_promesa).toLocaleString('es-AR')}</span>` : '';
                        const fechaPStr = h.fecha_promesa ? `<span class="text-slate-500">📆 ${h.fecha_promesa.split('-').reverse().join('/')}</span>` : '';
                        return `<div class="bg-white p-5 rounded-3xl shadow-sm text-xs border border-slate-100 mb-4 transition hover:shadow-md"><div class="flex justify-between font-black text-[9px] text-blue-500 mb-2 uppercase tracking-widest"><span>${fH}</span><span>Op: ${h.operador||'Sistema'}</span></div><div class="flex items-center gap-2 mb-2 flex-wrap">${estadoBadge}${montoStr}${fechaPStr}</div><p class="text-slate-600 font-semibold leading-relaxed">${h.observacion||'Sin texto'}</p></div>`;
                    }).join('') || '<p class="text-center text-slate-300 py-10 font-black tracking-widest uppercase text-[10px]">Sin gestiones previas</p>';
                    e.target.elements['observacion'].value = ''; load(document.getElementById('search').value); stats(); 
                } else alert("Error: " + d.message);
            } catch(err) { alert("Error."); } finally { btn.disabled = false; btn.innerText = 'Guardar Acción de Cobro'; }
        };
    }

    if(document.getElementById('loginForm')){
        document.getElementById('loginForm').onsubmit = async (e) => {
            e.preventDefault(); const btn = e.target.querySelector('button'), err = document.getElementById('lError');
            btn.disabled = true; btn.innerText = 'Cargando...'; err.classList.add('hidden');
            try {
                const res = await fetch('login.php', { method: 'POST', body: new FormData(e.target) });
                const d = await res.json();
                if(d.success) location.reload(); else { err.innerText = d.message; err.classList.remove('hidden'); btn.disabled = false; btn.innerText = 'Entrar'; }
            } catch (error) { btn.disabled = false; btn.innerText = 'Entrar'; }
        };
    }

    function exportarExcel() {
        const rows = document.querySelectorAll("#lista tr"); if(rows.length === 0) return alert("Vacio.");
        let csvContent = "Legajo,Razon,Cuotas,Total,Estado\n";
        rows.forEach(row => { const cols = row.querySelectorAll("td"); const data = Array.from(cols).slice(0, 5).map(td => '"' + td.innerText.replace(/,/g, ".").replace(/\n/g, " ") + '"'); csvContent += data.join(",") + "\n"; });
        const blob = new Blob(["\ufeff" + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement("a"); link.setAttribute("href", URL.createObjectURL(blob)); link.setAttribute("download", `cartera.csv`); link.click();
    }

    // ── ACCIONES MASIVAS ─────────────────────────────────────────────────────
    function getSeleccionados() {
        return Array.from(document.querySelectorAll('.chk-cliente:checked')).map(c => c.value);
    }
    function actualizarSeleccion() {
        const seleccionados = getSeleccionados();
        const barra = document.getElementById('barraMasiva');
        const count = document.getElementById('countSeleccionados');
        if (seleccionados.length > 0) {
            barra.classList.remove('hidden');
            barra.classList.add('flex');
            count.innerText = seleccionados.length + ' seleccionado' + (seleccionados.length > 1 ? 's' : '');
        } else {
            barra.classList.add('hidden');
            barra.classList.remove('flex');
        }
    }
    function seleccionarTodos(chk) {
        document.querySelectorAll('.chk-cliente').forEach(c => c.checked = chk.checked);
        actualizarSeleccion();
    }
    function deseleccionarTodos() {
        document.querySelectorAll('.chk-cliente').forEach(c => c.checked = false);
        const chkTodos = document.getElementById('chkTodos');
        if (chkTodos) chkTodos.checked = false;
        actualizarSeleccion();
    }
    async function accionMasiva(accion) {
        const legajos = getSeleccionados();
        if (legajos.length === 0) return;

        if (accion === 'eliminar') {
            if (!confirm(`¿Eliminar ${legajos.length} cliente(s) y todo su historial? Esta acción no se puede deshacer.`)) return;
        }

        const fd = new FormData();
        fd.append('accion', accion);
        fd.append('legajos', JSON.stringify(legajos));

        if (accion === 'asignar_operador') {
            fd.append('operador_id', document.getElementById('masivo_operador').value);
        }
        if (accion === 'cambiar_estado') {
            fd.append('estado', document.getElementById('masivo_estado').value);
        }

        try {
            const res = await fetch('api_masivo.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.success) {
                deseleccionarTodos();
                load(document.getElementById('search').value);
                stats();
            } else {
                alert('Error: ' + d.message);
            }
        } catch(e) { alert('Error de conexión.'); }
    }

    async function subirCSV(input) { if(!input.files[0]) return; const fd = new FormData(); fd.append('file', input.files[0]); const res = await fetch(api_importar, {method:'POST', body:fd}), d = await res.json(); if(d.success) { alert(d.count); load(); stats(); } }
    async function subirCSVAsignaciones(input) { if(!input.files[0]) return; const fd = new FormData(); fd.append('file', input.files[0]); try { const res = await fetch('api_importar_asignaciones.php', {method:'POST', body:fd}), d = await res.json(); if(d.success) { alert(d.count); load(); stats(); } } catch(e) {} }
    </script>
</body>
</html>