/**
 * Inicializa un mapa Leaflet con uno o varios pines.
 * window.VecigoMap.init(containerId, pins, options)
 * pins: [{ lat, lng, popupHtml?, nombre? }]
 */
(function (window) {
  'use strict';

  function ensureLeaflet(cb) {
    if (window.L) {
      cb();
      return;
    }
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(link);

    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js';
    s.onload = function () {
      cb();
    };
    s.onerror = function () {
      var el = document.querySelector('[data-map-error]');
      if (el) {
        el.hidden = false;
        el.textContent = 'No se pudo cargar el mapa. Revisa tu conexión a internet.';
      }
    };
    document.head.appendChild(s);
  }

  function init(containerId, pins, options) {
    options = options || {};
    ensureLeaflet(function () {
      var el = document.getElementById(containerId);
      if (!el || !window.L) return;

      // Leaflet colapsa si el contenedor tiene altura 0
      if (el.clientHeight < 100) {
        var targetH = (el.classList.contains('mapa-local') ? 300 : 480) + 'px';
        el.style.width = el.style.width || '100%';
        el.style.height = targetH;
        el.style.minHeight = targetH;
      }

      pins = (pins || []).filter(function (p) {
        return p && isFinite(p.lat) && isFinite(p.lng);
      });

      var center = options.center || (pins[0] ? [pins[0].lat, pins[0].lng] : [4.6097, -74.0817]);
      var zoom = options.zoom || 14;

      try {
        var DefaultIcon = L.icon({
          iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
          iconRetinaUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-2x.png',
          shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        });
        L.Marker.prototype.options.icon = DefaultIcon;

        var map = L.map(el, { scrollWheelZoom: true }).setView(center, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var bounds = [];
        pins.forEach(function (p) {
          var marker = L.marker([p.lat, p.lng]).addTo(map);
          if (p.popupHtml) {
            marker.bindPopup(p.popupHtml);
          } else if (p.nombre) {
            marker.bindPopup('<strong>' + p.nombre + '</strong>');
          }
          if (options.openFirst && bounds.length === 0) {
            marker.openPopup();
          }
          bounds.push([p.lat, p.lng]);
        });

        if (bounds.length > 1) {
          map.fitBounds(bounds, { padding: [48, 48], maxZoom: 15 });
        } else if (bounds.length === 1) {
          map.setView(bounds[0], options.zoom || 16);
        }

        setTimeout(function () {
          map.invalidateSize();
        }, 150);
        setTimeout(function () {
          map.invalidateSize();
        }, 500);

        window.addEventListener('resize', function () {
          map.invalidateSize();
        });
      } catch (err) {
        var errEl = document.querySelector('[data-map-error]');
        if (errEl) {
          errEl.hidden = false;
          errEl.textContent = 'No se pudo inicializar el mapa.';
        }
      }
    });
  }

  /**
   * Mapa clicable para elegir ubicación.
   * options: { latInput, lngInput, addressInput?, center?, zoom?, lat?, lng? }
   */
  function picker(containerId, options) {
    options = options || {};
    ensureLeaflet(function () {
      var el = document.getElementById(containerId);
      if (!el || !window.L) return;

      if (el.clientHeight < 100) {
        el.style.width = el.style.width || '100%';
        el.style.height = '320px';
        el.style.minHeight = '320px';
      }

      var latInput = options.latInput ? document.getElementById(options.latInput) : null;
      var lngInput = options.lngInput ? document.getElementById(options.lngInput) : null;
      var addressInput = options.addressInput ? document.getElementById(options.addressInput) : null;

      var startLat = parseFloat(options.lat);
      var startLng = parseFloat(options.lng);
      if ((!isFinite(startLat) || !isFinite(startLng)) && latInput && lngInput) {
        startLat = parseFloat(latInput.value);
        startLng = parseFloat(lngInput.value);
      }
      var hasStart = isFinite(startLat) && isFinite(startLng);
      var center = hasStart
        ? [startLat, startLng]
        : (options.center || [4.6097, -74.0817]);
      var zoom = options.zoom || (hasStart ? 16 : 13);

      try {
        var DefaultIcon = L.icon({
          iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
          iconRetinaUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-2x.png',
          shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        });
        L.Marker.prototype.options.icon = DefaultIcon;

        var map = L.map(el, { scrollWheelZoom: true }).setView(center, zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        var marker = null;

        function setPoint(lat, lng, reverse) {
          lat = Math.round(lat * 1e7) / 1e7;
          lng = Math.round(lng * 1e7) / 1e7;
          if (marker) {
            marker.setLatLng([lat, lng]);
          } else {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', function () {
              var p = marker.getLatLng();
              setPoint(p.lat, p.lng, true);
            });
          }
          if (latInput) latInput.value = String(lat);
          if (lngInput) lngInput.value = String(lng);
          if (reverse && addressInput) {
            reverseGeocode(lat, lng, addressInput);
          }
        }

        if (hasStart) {
          setPoint(startLat, startLng, false);
        }

        map.on('click', function (e) {
          setPoint(e.latlng.lat, e.latlng.lng, true);
        });

        setTimeout(function () { map.invalidateSize(); }, 150);
        setTimeout(function () { map.invalidateSize(); }, 500);
        window.addEventListener('resize', function () { map.invalidateSize(); });
      } catch (err) {
        var errEl = document.querySelector('[data-map-error]');
        if (errEl) {
          errEl.hidden = false;
          errEl.textContent = 'No se pudo cargar el selector del mapa.';
        }
      }
    });
  }

  function reverseGeocode(lat, lng, addressInput) {
    if (!addressInput || addressInput.value.trim() !== '') return;
    var url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
      + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng)
      + '&addressdetails=1&accept-language=es';
    fetch(url, { headers: { 'Accept': 'application/json' } })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (data) {
        if (!data || !data.display_name) return;
        if (addressInput.value.trim() === '') {
          addressInput.value = data.display_name.slice(0, 150);
        }
      })
      .catch(function () {});
  }

  window.VecigoMap = { init: init, picker: picker };
})(window);
