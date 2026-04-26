<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vista de Usuario - Mapa de Rutas y Puntos</title>
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
            width: 260px;
            max-height: 85vh;
            overflow-y: auto;
            font-size: 13px;
            backdrop-filter: blur(4px);
            pointer-events: auto;
        }
        .panel h3 {
            font-size: 16px;
            margin-bottom: 8px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }
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
            align-items: center;
            transition: background 0.2s;
        }
        .lista li:hover { background: #f0f0f0; }
        .color-muestra {
            width: 18px;
            height: 18px;
            border-radius: 4px;
            display: inline-block;
            margin-right: 8px;
            border: 1px solid #ccc;
        }
        .punto-muestra {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: #6c757d;
            display: inline-block;
            margin-right: 8px;
            border: 1px solid white;
            box-shadow: 0 0 1px black;
        }
        footer {
            font-size: 10px;
            text-align: center;
            margin-top: 12px;
            color: #666;
        }
    </style>
</head>
<body>
<div id="map"></div>
<div class="panel">
    <h3>📍 Puntos</h3>
    <ul id="lista-marcadores" class="lista"></ul>
    <hr>
    <h3>🛣️ Rutas</h3>
    <ul id="lista-rutas" class="lista"></ul>
    <footer>🔴 Rojo = fuera de área permitida<br>🟢 Área permitida (Facatativá ↔ Bogotá)<br>Haz clic en cualquier elemento para centrar el mapa</footer>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
    // ========== LÍMITES (misma zona restringida) ==========
    const bounds = L.latLngBounds([[4.65, -74.40], [4.85, -74.00]]);
    const map = L.map('map').setView([4.76, -74.22], 12);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> & CartoDB'
    }).addTo(map);
    map.setMaxBounds(bounds);
    map.on('drag', () => map.panInsideBounds(bounds, { animate: false }));
    map.setMinZoom(10);
    map.setMaxZoom(18);

    // Área restringida roja (exterior)
    const outer = [[90, -180], [90, 180], [-90, 180], [-90, -180]];
    const inner = [
        [bounds.getSouthWest().lat, bounds.getSouthWest().lng],
        [bounds.getNorthWest().lat, bounds.getNorthWest().lng],
        [bounds.getNorthEast().lat, bounds.getNorthEast().lng],
        [bounds.getSouthEast().lat, bounds.getSouthEast().lng]
    ];
    L.polygon([outer, inner], { color: 'red', fillColor: 'red', fillOpacity: 0.25 }).addTo(map);
    L.rectangle(bounds, { color: 'green', weight: 3, fill: false, dashArray: '8,6' }).addTo(map);

    // ========== VARIABLES ==========
    let markersLayer = L.layerGroup().addTo(map);
    let routesLayer = L.layerGroup().addTo(map);
    let markersData = {};     // id -> { marker, nombre, lat, lng, cantidad }
    let routesData = {};      // id -> { control, nombre, waypoints, valor, color }

    // ========== CARGAR PUNTOS (solo visualización, no arrastrables) ==========
    async function cargarMarcadores() {
        const resp = await fetch('api/get_markers.php');
        const markers = await resp.json();
        markersLayer.clearLayers();
        markersData = {};
        if (!Array.isArray(markers)) return;

        markers.forEach(m => {
            // Icono de punto gris fijo (no editable)
            let icon = L.divIcon({
                html: `<div style="background-color: #6c757d; width: 20px; height: 20px; border-radius: 50%; border: 2px solid white; box-shadow:0 0 2px black;"></div>`,
                iconSize: [20,20]
            });
            let marker = L.marker([parseFloat(m.lat), parseFloat(m.lng)], { draggable: false, icon: icon }).addTo(markersLayer);
            // Popup solo informativo (sin botones)
            marker.bindPopup(`<b>${m.nombre}</b><br>Cantidad: ${m.cantidad}`);
            markersData[m.id] = { marker, nombre: m.nombre, lat: parseFloat(m.lat), lng: parseFloat(m.lng), cantidad: m.cantidad };
        });
        actualizarListaPuntos();
    }

    function actualizarListaPuntos() {
        const ul = document.getElementById('lista-marcadores');
        ul.innerHTML = '';
        for (let id in markersData) {
            let m = markersData[id];
            let li = document.createElement('li');
            li.innerHTML = `<span class="punto-muestra"></span><span>${m.nombre} (${m.cantidad})</span>`;
            li.onclick = () => {
                map.setView([m.lat, m.lng], 15);
                m.marker.openPopup();
            };
            ul.appendChild(li);
        }
        if (!Object.keys(markersData).length) ul.innerHTML = '<li><em>No hay puntos</em></li>';
    }

    // ========== CARGAR RUTAS (solo visualización) ==========
    async function cargarRutas() {
        const resp = await fetch('api/get_routes.php');
        const rutas = await resp.json();
        // Limpiar rutas anteriores
        for (let id in routesData) {
            if (routesData[id].control) routesData[id].control.remove();
        }
        routesData = {};
        if (!Array.isArray(rutas)) return;

        // Calcular min y max para degradado (mismo que en admin)
        let valores = rutas.map(r => r.valor).filter(v => v !== undefined);
        let minVal = valores.length ? Math.min(...valores) : 0;
        let maxVal = valores.length ? Math.max(...valores) : 1;
        if (minVal === maxVal) maxVal = minVal + 1;

        for (let r of rutas) {
            let color = (() => {
                if (minVal === maxVal) return '#3b82f6';
                let ratio = (r.valor - minVal) / (maxVal - minVal);
                let red = Math.floor(255 * (1 - ratio));
                let blue = Math.floor(255 * ratio);
                return `rgb(${red}, 0, ${blue})`;
            })();

            let control = L.Routing.control({
                waypoints: r.waypoints.map(wp => L.latLng(wp.lat, wp.lng)),
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                lineOptions: { styles: [{ color: color, weight: 5, opacity: 0.8 }] },
                addWaypoints: false,
                draggableWaypoints: false,
                fitSelectedRoutes: false,
                showAlternatives: false,
                routeWhileDragging: false
            }).addTo(map);
            control.on('routesfound', () => {
                let container = control.getContainer();
                if (container) container.style.display = 'none'; // ocultar panel de instrucciones
            });
            routesData[r.id] = { control, nombre: r.nombre, waypoints: r.waypoints, valor: r.valor, color: color };
        }
        actualizarListaRutas();
    }

    function actualizarListaRutas() {
        const ul = document.getElementById('lista-rutas');
        ul.innerHTML = '';
        for (let id in routesData) {
            let r = routesData[id];
            let colorMuestra = `<span class="color-muestra" style="background-color: ${r.color}"></span>`;
            let li = document.createElement('li');
            li.innerHTML = `${colorMuestra}<span>${r.nombre} (valor: ${r.valor})</span>`;
            li.onclick = () => {
                if (r.waypoints.length) {
                    map.setView([r.waypoints[0].lat, r.waypoints[0].lng], 13);
                }
            };
            ul.appendChild(li);
        }
        if (!Object.keys(routesData).length) ul.innerHTML = '<li><em>No hay rutas</em></li>';
    }

    // ========== INICIALIZAR ==========
    cargarMarcadores();
    cargarRutas();
</script>
</body>
</html>