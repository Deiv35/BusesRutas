<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mapa Vehículos - Rutas editables</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; }
        #map { height: 100vh; width: 100%; }
        .panel {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            padding: 12px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 300px;
            max-height: 85vh;
            overflow-y: auto;
            font-size: 13px;
            backdrop-filter: blur(4px);
        }
        .panel h3 { font-size: 16px; margin-bottom: 8px; border-bottom: 1px solid #ddd; }
        .panel hr { margin: 10px 0; }
        .lista {
            list-style: none;
            padding: 0;
            margin: 0 0 8px 0;
        }
        .lista li {
            padding: 6px 8px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background 0.2s;
        }
        .lista li:hover { background: #f0f0f0; }
        .btn-small {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 11px;
            margin-left: 5px;
            cursor: pointer;
        }
        .btn-small.danger { background: #dc3545; }
        .btn-small.warning { background: #ffc107; color: black; }
        .btn-small.success { background: #28a745; }
        .acciones {
            display: flex;
            gap: 8px;
            margin: 8px 0;
            flex-wrap: wrap;
        }
        button { padding: 5px 10px; cursor: pointer; border: none; border-radius: 6px; font-weight: bold; }
        .modo-ruta { background: #28a745; color: white; }
        .modo-ruta.activo { background: #dc3545; }
        .color-muestra {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 8px;
            border: 1px solid #ccc;
        }
        .waypoint-marker {
            background: #ff9800;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            border: 2px solid white;
            box-shadow: 0 0 3px black;
            font-size: 14px;
        }
        footer { font-size: 10px; text-align: center; margin-top: 12px; color: #666; }
    </style>
</head>
<body>
<div id="map"></div>
<div class="panel">
    <h3>📍 Puntos</h3>
    <ul id="lista-marcadores" class="lista"></ul>
    <hr>
    <h3>🛣️ Rutas (color por Ruta)</h3>
    <div class="acciones">
        <button id="btn-nueva-ruta" class="modo-ruta">➕ Nueva ruta</button>
        <button id="btn-cancelar-ruta">❌ Cancelar</button>
    </div>
    <ul id="lista-rutas" class="lista"></ul>
    <footer>🔴 Rojo = fuera de área permitida<br>🟢 Área permitida (Facatativá ↔ Bogotá)<br>Editar ruta → botón "✏️ Waypoints"</footer>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // ========== LÍMITES ==========
    const bounds = L.latLngBounds([[4.65, -74.40], [4.85, -74.00]]);
    const map = L.map('map').setView([4.76, -74.22], 12);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> & CartoDB'
    }).addTo(map);
    map.setMaxBounds(bounds);
    map.on('drag', () => map.panInsideBounds(bounds, { animate: false }));
    map.setMinZoom(10);
    map.setMaxZoom(18);

    // Área restringida roja
    const outer = [[90, -180], [90, 180], [-90, 180], [-90, -180]];
    const inner = [[bounds.getSouthWest().lat, bounds.getSouthWest().lng],
                   [bounds.getNorthWest().lat, bounds.getNorthWest().lng],
                   [bounds.getNorthEast().lat, bounds.getNorthEast().lng],
                   [bounds.getSouthEast().lat, bounds.getSouthEast().lng]];
    L.polygon([outer, inner], { color: 'red', fillColor: 'red', fillOpacity: 0.25 }).addTo(map);
    L.rectangle(bounds, { color: 'green', weight: 3, fill: false, dashArray: '8,6' }).addTo(map);

    // ========== VARIABLES GLOBALES ==========
    let markersLayer = L.layerGroup().addTo(map);
    let routesLayer = L.layerGroup().addTo(map);
    let markersData = {};
    let routesData = {};
    
    let modoNuevaRuta = false;
    let waypointsTemp = [];
    let tempLayers = [];
    
    let editandoRutaId = null;
    let editWaypointsMarkers = [];
    let currentEditButtons = null;

    // ========== DEGRADADO PARA RUTAS ==========
    function getRouteColor(valor, minVal, maxVal) {
        if (minVal === maxVal) return '#3b82f6';
        let ratio = (valor - minVal) / (maxVal - minVal);
        let r = Math.floor(255 * (1 - ratio));
        let b = Math.floor(255 * ratio);
        return `rgb(${r}, 0, ${b})`;
    }

    // ========== PUNTOS ==========
    async function cargarMarcadores() {
        const resp = await fetch('api/get_markers.php');
        const markers = await resp.json();
        markersLayer.clearLayers();
        markersData = {};
        if (!Array.isArray(markers)) return;
        markers.forEach(m => {
            let icon = L.divIcon({
                html: `<div style="background-color: #6c757d; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow:0 0 2px black;"></div>`,
                iconSize: [20,20]
            });
            let marker = L.marker([parseFloat(m.lat), parseFloat(m.lng)], { draggable: true, icon: icon }).addTo(markersLayer);
            marker.bindPopup(`<b>${m.nombre}</b><br>Cantidad: ${m.cantidad}<br>
                <button class="btn-small" onclick="editarPunto(${m.id}, '${m.nombre.replace(/'/g, "\\'")}', ${m.cantidad})">Editar</button>
                <button class="btn-small danger" onclick="eliminarPunto(${m.id})">Eliminar</button>`);
            marker.on('dragend', async (e) => {
                let pos = marker.getLatLng();
                if (!bounds.contains(pos)) { marker.setLatLng([m.lat, m.lng]); alert("Fuera del área permitida"); return; }
                await actualizarPunto(m.id, m.nombre, pos.lat, pos.lng, m.cantidad);
                for (let rid in routesData) {
                    let r = routesData[rid];
                    let necesita = r.waypoints.some(wp => Math.abs(wp.lat - m.lat) < 0.00001 && Math.abs(wp.lng - m.lng) < 0.00001);
                    if (necesita) await recalcularRuta(rid);
                }
            });
            markersData[m.id] = { marker, nombre: m.nombre, lat: parseFloat(m.lat), lng: parseFloat(m.lng), cantidad: m.cantidad };
        });
        actualizarListaPuntos();
    }

    async function actualizarPunto(id, nombre, lat, lng, cantidad) {
        await fetch('api/update_marker.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id, nombre, lat, lng, cantidad })
        });
        let punto = markersData[id];
        if (punto) {
            punto.nombre = nombre;
            punto.lat = lat;
            punto.lng = lng;
            punto.cantidad = cantidad;
            punto.marker.setLatLng([lat, lng]);
            punto.marker.setPopupContent(`<b>${nombre}</b><br>Cantidad: ${cantidad}<br>
                <button class="btn-small" onclick="editarPunto(${id}, '${nombre.replace(/'/g, "\\'")}', ${cantidad})">Editar</button>
                <button class="btn-small danger" onclick="eliminarPunto(${id})">Eliminar</button>`);
        }
        actualizarListaPuntos();
    }

    window.editarPunto = function(id, nombreActual, cantidadActual) {
        let nuevoNombre = prompt('Nuevo nombre:', nombreActual);
        let nuevaCant = parseInt(prompt('Cantidad:', cantidadActual));
        if (isNaN(nuevaCant)) nuevaCant = cantidadActual;
        let p = markersData[id];
        actualizarPunto(id, nuevoNombre || nombreActual, p.lat, p.lng, nuevaCant);
    };

    window.eliminarPunto = async function(id) {
        if (!confirm('¿Eliminar punto?')) return;
        await fetch('api/delete_marker.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
        markersLayer.removeLayer(markersData[id].marker);
        delete markersData[id];
        actualizarListaPuntos();
        cargarRutas();
    };

    function actualizarListaPuntos() {
        const ul = document.getElementById('lista-marcadores');
        ul.innerHTML = '';
        for (let id in markersData) {
            let m = markersData[id];
            let li = document.createElement('li');
            li.innerHTML = `<span>${m.nombre} (${m.cantidad})</span>
                            <button class="btn-small" onclick="editarPunto(${id}, '${m.nombre.replace(/'/g, "\\'")}', ${m.cantidad})">Editar</button>
                            <button class="btn-small danger" onclick="eliminarPunto(${id})">Eliminar</button>`;
            li.onclick = (e) => { if(e.target.tagName!=='BUTTON') { map.setView([m.lat, m.lng], 15); m.marker.openPopup(); } };
            ul.appendChild(li);
        }
        if (!Object.keys(markersData).length) ul.innerHTML = '<li><em>Sin puntos</em></li>';
    }

    // ========== RUTAS ==========
    async function cargarRutas() {
        const resp = await fetch('api/get_routes.php');
        const rutas = await resp.json();
        for (let id in routesData) {
            if (routesData[id].control) routesData[id].control.remove();
        }
        routesData = {};
        if (!Array.isArray(rutas)) return;
        let valores = rutas.map(r => r.valor).filter(v => v !== undefined);
        let minVal = valores.length ? Math.min(...valores) : 0;
        let maxVal = valores.length ? Math.max(...valores) : 1;
        if (minVal === maxVal) maxVal = minVal + 1;
        for (let r of rutas) {
            let color = getRouteColor(r.valor, minVal, maxVal);
            let control = L.Routing.control({
                waypoints: r.waypoints.map(wp => L.latLng(wp.lat, wp.lng)),
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                lineOptions: { styles: [{ color: color, weight: 5, opacity: 0.8 }] },
                addWaypoints: false, draggableWaypoints: false, fitSelectedRoutes: false,
                showAlternatives: false, routeWhileDragging: false
            }).addTo(map);
            control.on('routesfound', () => { let c = control.getContainer(); if(c) c.style.display = 'none'; });
            routesData[r.id] = { control, nombre: r.nombre, waypoints: r.waypoints, valor: r.valor, color: color };
        }
        actualizarListaRutas();
    }

    async function recalcularRuta(id) {
        let r = routesData[id];
        if (!r) return;
        r.control.remove();
        let nuevosWaypoints = r.waypoints.map(wp => {
            let puntoActualizado = Object.values(markersData).find(m => Math.abs(m.lat - wp.lat) < 0.00001 && Math.abs(m.lng - wp.lng) < 0.00001);
            return puntoActualizado ? L.latLng(puntoActualizado.lat, puntoActualizado.lng) : L.latLng(wp.lat, wp.lng);
        });
        let nuevoControl = L.Routing.control({
            waypoints: nuevosWaypoints,
            router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
            lineOptions: { styles: [{ color: r.color, weight: 5 }] },
            addWaypoints: false, draggableWaypoints: false
        }).addTo(map);
        nuevoControl.on('routesfound', () => { let c = nuevoControl.getContainer(); if(c) c.style.display = 'none'; });
        routesData[id].control = nuevoControl;
        routesData[id].waypoints = nuevosWaypoints.map(wp => ({ lat: wp.lat, lng: wp.lng }));
        await actualizarRutaEnBD(id, routesData[id].nombre, routesData[id].waypoints, routesData[id].valor);
    }

    async function actualizarRutaEnBD(id, nombre, waypoints, valor) {
        await fetch('api/update_route.php', {
            method: 'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ id, nombre, waypoints, valor })
        });
    }

    async function guardarNuevaRuta(nombre, waypoints, valor) {
        const resp = await fetch('api/add_route.php', {
            method: 'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ nombre, waypoints, valor })
        });
        if ((await resp.json()).success) cargarRutas();
        else alert('Error al guardar ruta');
    }

    window.editarRuta = function(id, nombreActual, valorActual) {
        let nuevoNombre = prompt('Nuevo nombre:', nombreActual);
        let nuevoValor = parseInt(prompt('Numero de Carrera (0-100):', valorActual));
        if (isNaN(nuevoValor)) nuevoValor = valorActual;
        let r = routesData[id];
        r.nombre = nuevoNombre || nombreActual;
        r.valor = nuevoValor;
        actualizarRutaEnBD(id, r.nombre, r.waypoints, r.valor);
        cargarRutas();
    };

    window.eliminarRuta = async function(id) {
        if (!confirm('¿Eliminar ruta?')) return;
        await fetch('api/delete_route.php', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
        routesData[id].control.remove();
        delete routesData[id];
        actualizarListaRutas();
    };

    function actualizarListaRutas() {
        const ul = document.getElementById('lista-rutas');
        ul.innerHTML = '';
        for (let id in routesData) {
            let r = routesData[id];
            let colorMuestra = `<span class="color-muestra" style="background-color: ${r.color}"></span>`;
            let li = document.createElement('li');
            li.innerHTML = `${colorMuestra}<span>${r.nombre} (valor: ${r.valor})</span>
                            <button class="btn-small" onclick="editarRuta(${id}, '${r.nombre.replace(/'/g, "\\'")}', ${r.valor})">Editar</button>
                            <button class="btn-small warning" onclick="iniciarEdicionWaypoints(${id})">✏️ Waypoints</button>
                            <button class="btn-small danger" onclick="eliminarRuta(${id})">Elim</button>`;
            li.onclick = (e) => { if(e.target.tagName!=='BUTTON') map.setView([r.waypoints[0].lat, r.waypoints[0].lng], 13); };
            ul.appendChild(li);
        }
        if (!Object.keys(routesData).length) ul.innerHTML = '<li><em>Sin rutas</em></li>';
    }

    // ========== EDICIÓN DE WAYPOINTS ==========
    function iniciarEdicionWaypoints(rutaId) {
        if (editandoRutaId !== null) {
            alert("Ya estás editando otra ruta. Finaliza esa edición primero.");
            return;
        }
        if (modoNuevaRuta) cancelarModoNuevaRuta();
        editandoRutaId = rutaId;
        const r = routesData[rutaId];
        if (!r) return;
        r.waypoints.forEach((wp, idx) => {
            let marker = L.marker([wp.lat, wp.lng], {
                draggable: true,
                icon: L.divIcon({ className: 'waypoint-marker', html: (idx+1).toString(), iconSize: [28,28] })
            }).addTo(map);
            marker.bindPopup(`Waypoint ${idx+1}<br><button class="btn-small danger" onclick="eliminarWaypoint(${rutaId}, ${idx})">Eliminar</button>`);
            marker.on('dragend', async (e) => {
                let newPos = marker.getLatLng();
                if (!bounds.contains(newPos)) { marker.setLatLng([wp.lat, wp.lng]); alert("Fuera del área permitida"); return; }
                r.waypoints[idx] = { lat: newPos.lat, lng: newPos.lng };
                await actualizarRutaEnBD(rutaId, r.nombre, r.waypoints, r.valor);
                await recalcularRuta(rutaId);
                marker.setIcon(L.divIcon({ className: 'waypoint-marker', html: (idx+1).toString(), iconSize: [28,28] }));
            });
            editWaypointsMarkers.push(marker);
        });
        let btnFinalizar = document.createElement('button');
        btnFinalizar.textContent = '✅ Finalizar edición de waypoints';
        btnFinalizar.className = 'btn-small success';
        btnFinalizar.style.marginTop = '8px';
        btnFinalizar.onclick = () => cancelarEdicionWaypoints();
        document.getElementById('lista-rutas').parentNode.appendChild(btnFinalizar);
        currentEditButtons = btnFinalizar;
        alert("Edición activa: arrastra marcadores naranjas, haz clic en mapa para agregar waypoints. Usa 'Finalizar' cuando termines.");
    }

    function agregarWaypointAEdicion(latlng) {
        if (editandoRutaId === null) return;
        if (!bounds.contains(latlng)) { alert("Fuera del área permitida"); return; }
        const r = routesData[editandoRutaId];
        const nuevoWaypoint = { lat: latlng.lat, lng: latlng.lng };
        r.waypoints.push(nuevoWaypoint);
        actualizarRutaEnBD(editandoRutaId, r.nombre, r.waypoints, r.valor).then(async () => {
            await recalcularRuta(editandoRutaId);
            const nuevoIndice = r.waypoints.length - 1;
            let marker = L.marker([latlng.lat, latlng.lng], {
                draggable: true,
                icon: L.divIcon({ className: 'waypoint-marker', html: (nuevoIndice+1).toString(), iconSize: [28,28] })
            }).addTo(map);
            marker.bindPopup(`Waypoint ${nuevoIndice+1}<br><button class="btn-small danger" onclick="eliminarWaypoint(${editandoRutaId}, ${nuevoIndice})">Eliminar</button>`);
            marker.on('dragend', async (e) => {
                let newPos = marker.getLatLng();
                if (!bounds.contains(newPos)) { marker.setLatLng([nuevoWaypoint.lat, nuevoWaypoint.lng]); alert("Fuera del área permitida"); return; }
                r.waypoints[nuevoIndice] = { lat: newPos.lat, lng: newPos.lng };
                await actualizarRutaEnBD(editandoRutaId, r.nombre, r.waypoints, r.valor);
                await recalcularRuta(editandoRutaId);
                marker.setIcon(L.divIcon({ className: 'waypoint-marker', html: (nuevoIndice+1).toString(), iconSize: [28,28] }));
            });
            editWaypointsMarkers.push(marker);
        });
    }

    window.eliminarWaypoint = function(rutaId, index) {
        const r = routesData[rutaId];
        if (r.waypoints.length <= 2) {
            alert("La ruta debe tener al menos 2 waypoints.");
            return;
        }
        r.waypoints.splice(index, 1);
        actualizarRutaEnBD(rutaId, r.nombre, r.waypoints, r.valor).then(async () => {
            await recalcularRuta(rutaId);
            cancelarEdicionWaypoints();
            iniciarEdicionWaypoints(rutaId);
        });
    };

    function cancelarEdicionWaypoints() {
        if (editandoRutaId === null) return;
        editWaypointsMarkers.forEach(m => map.removeLayer(m));
        editWaypointsMarkers = [];
        if (currentEditButtons) currentEditButtons.remove();
        editandoRutaId = null;
    }

    // ========== MODO NUEVA RUTA ==========
    function iniciarModoNuevaRuta() {
        if (editandoRutaId !== null) cancelarEdicionWaypoints();
        modoNuevaRuta = true;
        waypointsTemp = [];
        document.getElementById('btn-nueva-ruta').classList.add('activo');
        document.getElementById('btn-nueva-ruta').textContent = '🟢 Seleccionando... (doble clic termina)';
        map.getContainer().style.cursor = 'crosshair';
        alert('Modo nueva ruta: haz clic en puntos o en el mapa para agregar waypoints. Doble clic para finalizar.');
    }
    function cancelarModoNuevaRuta() {
        modoNuevaRuta = false;
        tempLayers.forEach(l => map.removeLayer(l));
        tempLayers = [];
        waypointsTemp = [];
        document.getElementById('btn-nueva-ruta').classList.remove('activo');
        document.getElementById('btn-nueva-ruta').textContent = '➕ Nueva ruta';
        map.getContainer().style.cursor = '';
    }
    function agregarWaypointTemporal(latlng) {
        waypointsTemp.push(latlng);
        if (waypointsTemp.length > 1) {
            let line = L.polyline([waypointsTemp[waypointsTemp.length-2], latlng], { color: 'orange', weight: 3, dashArray: '5,8' }).addTo(map);
            tempLayers.push(line);
        }
        let tempMarker = L.marker(latlng, { icon: L.divIcon({ className: 'temp-waypoint', html: '●', iconSize: [12,12] }) }).addTo(map);
        tempLayers.push(tempMarker);
    }
    
    // ========== EVENTO DOBLE CLIC CORREGIDO ==========
    map.on('dblclick', async (e) => {
        if (!modoNuevaRuta) return;
        e.originalEvent.stopPropagation();
        
        if (waypointsTemp.length < 2) {
            alert("Necesitas al menos dos puntos.");
            cancelarModoNuevaRuta();
            return;
        }
        
        let nombre = prompt('Nombre de la ruta:');
        if (!nombre || nombre.trim() === "") {
            cancelarModoNuevaRuta();
            return;
        }
        
        let valor = prompt('Valor para degradado (0-100):', '50');
        if (valor === null) {
            cancelarModoNuevaRuta();
            return;
        }
        valor = parseInt(valor);
        if (isNaN(valor)) valor = 0;
        
        let waypoints = waypointsTemp.map(wp => ({ lat: wp.lat, lng: wp.lng }));
        await guardarNuevaRuta(nombre.trim(), waypoints, valor);
        cancelarModoNuevaRuta();
    });

    // ========== EVENTO CLIC GLOBAL ==========
    map.on('click', (e) => {
        if (!bounds.contains(e.latlng)) {
            if (modoNuevaRuta || editandoRutaId !== null) alert("Fuera del área permitida");
            return;
        }
        if (modoNuevaRuta) {
            agregarWaypointTemporal(e.latlng);
        } else if (editandoRutaId !== null) {
            agregarWaypointAEdicion(e.latlng);
        } else {
            crearNuevoPunto(e.latlng);
        }
    });

    async function crearNuevoPunto(latlng) {
        let nombre = prompt('Nombre del punto:');
        if (!nombre) return;
        let cantidad = parseInt(prompt('Cantidad (0-100):', '0'));
        if (isNaN(cantidad)) cantidad = 0;
        const resp = await fetch('api/add_marker.php', {
            method: 'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({ nombre, lat: latlng.lat, lng: latlng.lng, cantidad })
        });
        if ((await resp.json()).success) cargarMarcadores();
        else alert('Error al guardar punto');
    }

    document.getElementById('btn-nueva-ruta').onclick = () => {
        if (modoNuevaRuta) cancelarModoNuevaRuta();
        else iniciarModoNuevaRuta();
    };
    document.getElementById('btn-cancelar-ruta').onclick = () => cancelarModoNuevaRuta();

    cargarMarcadores();
    cargarRutas();
</script>
</body>
</html>
