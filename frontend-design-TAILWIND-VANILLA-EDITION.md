---
name: frontend-design (CRM.ROQUE Edition)
description: >-
  Create distinctive, production-grade frontend interfaces with high design quality.
  USE THIS SKILL ALWAYS for:
  - Tailwind CSS v2 + Vanilla JavaScript (NO frameworks, NO React)
  - Modal systems with Fetch API for data loading
  - SPA (Single Page Applications) with tab switching
  - CRM dashboards, forms, data tables, interactive UIs
  - Mentioning "Tailwind", "vanilla JavaScript", "modal", "Fetch API", "SPA"
  - Any HTML/CSS/JS that needs polish and production-grade aesthetics
  Generates creative, polished code avoiding generic AI aesthetics.
  CRITICAL: Always reference the "Tailwind v2 + Vanilla JS" and "Modal System Patterns" sections.
license: Customized for CRM.ROQUE Development
---

# Frontend Design — Tailwind v2 + Vanilla JS Edition

## Design Thinking

Before coding, understand the context and commit to a BOLD aesthetic direction:
- **Purpose**: What problem does this interface solve? Who uses it?
- **Tone**: Pick an extreme: brutally minimal, maximalist chaos, retro-futuristic, organic/natural, luxury/refined, playful/toy-like, editorial/magazine, brutalist/raw, art deco/geometric, soft/pastel, industrial/utilitarian, etc.
- **Constraints**: Technical requirements (Tailwind v2, Vanilla JS, Fetch API, no external frameworks).
- **Differentiation**: What makes this UNFORGETTABLE? What's the one thing someone will remember?

**CRITICAL**: Choose a clear conceptual direction and execute it with precision. Bold maximalism and refined minimalism both work - the key is intentionality, not intensity.

Then implement working code that is:
- Production-grade and functional
- Visually striking and memorable
- Cohesive with a clear aesthetic point-of-view
- Meticulously refined in every detail

---

## Frontend Aesthetics Guidelines

Focus on:
- **Typography**: Choose fonts that are beautiful, unique, and interesting. Avoid generic fonts like Arial and Inter; opt instead for distinctive choices that elevate the frontend's aesthetics; unexpected, characterful font choices. Pair a distinctive display font with a refined body font.
- **Color & Theme**: Commit to a cohesive aesthetic. Use CSS variables for consistency. Dominant colors with sharp accents outperform timid, evenly-distributed palettes.
- **Motion**: Use animations for effects and micro-interactions. Prioritize CSS-only solutions for HTML. Focus on high-impact moments: one well-orchestrated page load with staggered reveals (animation-delay) creates more delight than scattered micro-interactions. Use scroll-triggering and hover states that surprise.
- **Spatial Composition**: Unexpected layouts. Asymmetry. Overlap. Diagonal flow. Grid-breaking elements. Generous negative space OR controlled density.
- **Backgrounds & Visual Details**: Create atmosphere and depth rather than defaulting to solid colors. Add contextual effects and textures that match the overall aesthetic. Apply creative forms like gradient meshes, noise textures, geometric patterns, layered transparencies, dramatic shadows, decorative borders, custom cursors, and grain overlays.

NEVER use generic AI-generated aesthetics like overused font families (Inter, Roboto, Arial, system fonts), cliched color schemes (particularly purple gradients on white backgrounds), predictable layouts and component patterns, and cookie-cutter design that lacks context-specific character.

Interpret creatively and make unexpected choices that feel genuinely designed for the context. No design should be the same. Vary between light and dark themes, different fonts, different aesthetics.

**IMPORTANT**: Match implementation complexity to the aesthetic vision. Maximalist designs need elaborate code with extensive animations and effects. Minimalist or refined designs need restraint, precision, and careful attention to spacing, typography, and subtle details. Elegance comes from executing the vision well.

---

## 🎯 Tailwind v2 + Vanilla JS Stack

### 1. Estructura Base (HTML + Tailwind)

```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplicación</title>
    <!-- Tailwind v2 vía CDN (local o desde cdn.tailwindcss.com) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts para tipografía distintiva -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        /* Variables CSS personalizadas */
        :root { --color-primary: #2563eb; --color-accent: #f97316; }
    </style>
</head>
<body class="bg-slate-50 text-slate-900">
    <!-- Contenido -->
    <script>
        // JavaScript vanilla aquí (ver sección 2)
    </script>
</body>
</html>
```

### 2. Vanilla JavaScript Patterns (Sin Frameworks)

**Pattern A: Event Listeners Simples**
```javascript
// ✅ Seleccionar elementos
const btn = document.getElementById('btn-guardar');
const input = document.querySelector('input[name="email"]');
const items = document.querySelectorAll('.item');

// ✅ Event listeners
btn.addEventListener('click', async (e) => {
    e.preventDefault();
    const email = input.value;
    console.log(email);
});

// ✅ Delegación de eventos (para elementos dinámicos)
document.addEventListener('click', (e) => {
    if (e.target.matches('.btn-delete')) {
        const id = e.target.dataset.id;
        eliminar(id);
    }
});
```

**Pattern B: Fetch API (Comunicación con Backend)**
```javascript
async function cargarDatos() {
    try {
        const res = await fetch('api_clientes.php?action=search&q=texto');
        const data = await res.json();
        
        if (data.success) {
            console.log(data.data);  // Procesar respuesta
            renderizarTabla(data.data);
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error de conexión:', error);
        alert('Error al cargar datos');
    }
}

// Llamar función
cargarDatos();
```

**Pattern C: POST con FormData**
```javascript
async function guardarGestion(e) {
    e.preventDefault();
    const fd = new FormData(e.target);  // Captura todo el <form>
    
    try {
        const res = await fetch('api_gestion.php', { 
            method: 'POST', 
            body: fd 
        });
        const d = await res.json();
        
        if (d.success) {
            alert('✅ Guardado');
            e.target.reset();
        } else {
            alert('❌ ' + d.message);
        }
    } catch (err) {
        alert('Error de conexión');
    }
}

// En HTML: <form onsubmit="guardarGestion(event)">
```

**Pattern D: DOM Manipulation (Mostrar/Ocultar)**
```javascript
// Toggle (mostrar/ocultar)
function toggleModal(show) {
    const modal = document.getElementById('modal');
    if (show) {
        modal.classList.remove('hidden');  // Tailwind: hidden
    } else {
        modal.classList.add('hidden');
    }
}

// Actualizar contenido
function mostrarResultados(items) {
    const container = document.getElementById('resultados');
    container.innerHTML = items.map(item => `
        <div class="p-4 bg-white rounded-lg">
            <h3>${item.nombre}</h3>
            <p>${item.descripcion}</p>
        </div>
    `).join('');
}

// Agregar clase con condición
const element = document.querySelector('.card');
if (estaActivo) {
    element.classList.add('border-green-500', 'shadow-lg');
} else {
    element.classList.remove('border-green-500', 'shadow-lg');
}
```

### 3. Estructura de Tabs/Secciones (SPA)

```javascript
function switchTab(tabName) {
    // Ocultar todas las secciones
    document.querySelectorAll('[id^="sec-"]').forEach(sec => {
        sec.classList.add('hidden');
    });
    
    // Mostrar la sección seleccionada
    const activeTab = document.getElementById(`sec-${tabName}`);
    if (activeTab) {
        activeTab.classList.remove('hidden');
    }
    
    // Actualizar estilos del botón activo
    document.querySelectorAll('[id^="btn-"]').forEach(btn => {
        btn.classList.toggle('tab-active', btn.id === `btn-${tabName}`);
    });
}

// En HTML:
// <button onclick="switchTab('clientes')" id="btn-clientes">Clientes</button>
// <section id="sec-clientes" class="hidden">Contenido Clientes</section>
```

### 4. Búsqueda en Tiempo Real (Debounce)

```javascript
let searchTimeout;

document.getElementById('search-input').addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    
    searchTimeout = setTimeout(async () => {
        const query = e.target.value;
        if (query.length < 2) return;
        
        const res = await fetch(`api_clientes.php?q=${encodeURIComponent(query)}`);
        const data = await res.json();
        
        mostrarResultados(data);
    }, 400);  // Esperar 400ms después de parar de escribir
});
```

---

## 🎭 Modal System Patterns

### 1. Modal Básico (Abrir/Cerrar)

```html
<!-- MODAL (Oculto por defecto) -->
<div id="modal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden items-center justify-center p-4 z-50" onclick="closeModal()">
    <div class="bg-white p-8 rounded-3xl w-full max-w-md shadow-2xl" onclick="event.stopPropagation()">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-2xl font-black text-slate-800">Título Modal</h3>
            <button onclick="closeModal()" class="text-slate-400 hover:text-slate-800 text-2xl">✕</button>
        </div>
        
        <form onsubmit="guardar(event)">
            <input type="text" placeholder="Ingresa datos..." required class="w-full px-4 py-3 bg-slate-50 border rounded-xl mb-4 outline-none">
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold uppercase hover:bg-blue-700 transition">Guardar</button>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modal').classList.replace('hidden', 'flex');
    }
    
    function closeModal() {
        document.getElementById('modal').classList.replace('flex', 'hidden');
    }
</script>
```

### 2. Modal con Animación de Escala

```html
<div id="modal" class="fixed inset-0 bg-black/70 backdrop-blur-md hidden items-center justify-center p-4 z-50" onclick="closeModal()">
    <div id="modalContent" class="bg-white p-8 rounded-3xl w-full max-w-2xl shadow-2xl scale-95 transition-all duration-300" onclick="event.stopPropagation()">
        <!-- Contenido -->
    </div>
</div>

<script>
    function openModal(data) {
        document.getElementById('modal').classList.replace('hidden', 'flex');
        // Trigger animación
        setTimeout(() => {
            document.getElementById('modalContent').classList.replace('scale-95', 'scale-100');
        }, 10);
    }
    
    function closeModal() {
        document.getElementById('modalContent').classList.replace('scale-100', 'scale-95');
        setTimeout(() => {
            document.getElementById('modal').classList.replace('flex', 'hidden');
        }, 300);
    }
</script>
```

### 3. Modal con Fetch API (Cargar Datos)

```javascript
async function abrirDetalles(id) {
    try {
        // 1. Cargar datos del backend
        const res = await fetch(`api_clientes.php?id=${id}`);
        const data = await res.json();
        
        // 2. Rellenar modal con datos
        document.getElementById('modalNombre').innerText = data.razon_social;
        document.getElementById('modalLegajo').innerText = data.legajo;
        document.getElementById('modalTotal').innerText = `$${data.total_vencido}`;
        
        // 3. Abrir modal
        document.getElementById('modal').classList.remove('hidden');
        
    } catch (error) {
        alert('Error al cargar datos: ' + error.message);
    }
}

// En HTML:
// <button onclick="abrirDetalles(123)">Ver Detalles</button>
```

### 4. Modal con Formulario y Guardado

```html
<div id="modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="closeModal()">
    <div class="bg-white p-10 rounded-3xl w-full max-w-md shadow-2xl" onclick="event.stopPropagation()">
        <h3 class="text-2xl font-black mb-6">Nueva Gestión</h3>
        
        <form id="formGestion" onsubmit="guardarGestion(event)">
            <select name="estado" required class="w-full px-4 py-3 bg-slate-50 border rounded-xl mb-4 outline-none">
                <option value="promesa">Promesa de Pago</option>
                <option value="no_responde">No Responde</option>
                <option value="al_dia">Al Día</option>
            </select>
            
            <input type="date" name="fecha_promesa" class="w-full px-4 py-3 bg-slate-50 border rounded-xl mb-4 outline-none">
            
            <textarea name="observacion" rows="3" placeholder="Notas..." required class="w-full px-4 py-3 bg-slate-50 border rounded-xl mb-4 outline-none resize-none"></textarea>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold uppercase hover:bg-blue-700 transition">Guardar Gestión</button>
        </form>
    </div>
</div>

<script>
    async function guardarGestion(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        
        try {
            const res = await fetch('api_gestion.php', { 
                method: 'POST', 
                body: fd 
            });
            const d = await res.json();
            
            if (d.success) {
                alert('✅ Guardado');
                closeModal();
                cargarListado();  // Recargar tabla
            } else {
                alert('❌ ' + d.message);
            }
        } catch (err) {
            alert('Error: ' + err.message);
        }
    }
</script>
```

### 5. Modal con Tabs Internos

```html
<div id="modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50" onclick="closeModal()">
    <div class="bg-white rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden" onclick="event.stopPropagation()">
        
        <!-- Header con Tabs -->
        <div class="flex border-b bg-slate-50">
            <button class="flex-1 px-6 py-4 font-bold uppercase text-sm border-b-2 border-blue-600 text-blue-600" onclick="switchModalTab('datos')">Datos</button>
            <button class="flex-1 px-6 py-4 font-bold uppercase text-sm border-b-2 border-transparent text-slate-400" onclick="switchModalTab('historial')">Historial</button>
        </div>
        
        <!-- Contenido de Tabs -->
        <div class="p-8">
            <div id="tab-datos" class="block">
                <!-- Datos del cliente -->
            </div>
            <div id="tab-historial" class="hidden">
                <!-- Historial de gestiones -->
            </div>
        </div>
    </div>
</div>

<script>
    function switchModalTab(tab) {
        document.querySelectorAll('[id^="tab-"]').forEach(t => t.classList.add('hidden'));
        document.getElementById(`tab-${tab}`).classList.remove('hidden');
    }
</script>
```

---

## ✅ Checklist para Implementar

Antes de terminar cualquier interfaz:

- [ ] ¿Usa Tailwind v2 (sin frameworks)?
- [ ] ¿Todo es Vanilla JavaScript (sin jQuery, React, Vue)?
- [ ] ¿Los modales usan `classList.replace()` o `classList.toggle()`?
- [ ] ¿Fetch API con try/catch correcto?
- [ ] ¿FormData para POST en lugar de JSON manual?
- [ ] ¿Los datos del backend devuelven `{success: true, data: ...}`?
- [ ] ¿Hay debounce en búsquedas en tiempo real?
- [ ] ¿Animaciones suaves (scale, fade, slide)?
- [ ] ¿Tipografía distintiva (no Inter, no Roboto)?
- [ ] ¿Color scheme coherente (dominante + accent)?
- [ ] ¿Hover states en botones e inputs?
- [ ] ¿Responsive design (mobile-first)?

---

## 🚀 Ejemplos Rápidos por Caso de Uso

### Caso: Tabla con búsqueda + modal de edición
```javascript
// 1. Cargar tabla
async function cargarTabla() {
    const res = await fetch('api_clientes.php');
    const items = await res.json();
    renderizarTabla(items);
}

// 2. Renderizar tabla
function renderizarTabla(items) {
    const tabla = document.getElementById('tabla');
    tabla.innerHTML = items.map(item => `
        <tr class="border-b hover:bg-slate-50 cursor-pointer" ondblclick="abrirModal(${JSON.stringify(item)})">
            <td class="px-6 py-4">${item.legajo}</td>
            <td class="px-6 py-4">${item.razon_social}</td>
            <td class="px-6 py-4">$${item.total_vencido}</td>
        </tr>
    `).join('');
}

// 3. Abrir modal con datos
function abrirModal(item) {
    document.getElementById('modalLegajo').value = item.legajo;
    document.getElementById('modalNombre').value = item.razon_social;
    document.getElementById('modal').classList.remove('hidden');
}

// 4. Inicializar
window.addEventListener('DOMContentLoaded', cargarTabla);
```

---

## 🎨 Patrones de Diseño Recomendados para CRM

### Colores CRM.ROQUE
```css
:root {
    --primary: #2563eb;      /* Azul para acciones */
    --accent: #f97316;       /* Naranja para alertas */
    --danger: #dc2626;       /* Rojo para eliminaciones */
    --success: #16a34a;      /* Verde para éxito */
    --warning: #ea580c;      /* Naranja para advertencias */
}
```

### Tipografía
```css
/* Display: Plus Jakarta Sans Bold (títulos) */
h1, h2, h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; }

/* Body: Plus Jakarta Sans Regular (texto) */
body { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 400; }
```

### Espaciado Consistente
```html
<!-- Heading + Descripción -->
<h3 class="text-2xl font-black mb-2">Título</h3>
<p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-6">Subtítulo</p>

<!-- Cards -->
<div class="bg-white p-8 rounded-[2.5rem] border shadow-sm">
    Contenido
</div>

<!-- Botones -->
<button class="bg-blue-600 text-white px-6 py-4 rounded-2xl font-black uppercase text-xs hover:bg-blue-700 transition shadow-xl">
    Acción
</button>
```

Remember: Claude is capable of extraordinary creative work. Don't hold back, show what can truly be created when thinking outside the box and committing fully to a distinctive vision.
