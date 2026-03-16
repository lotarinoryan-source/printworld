// ===== GOOGLE MAPS INTEGRATION =====
let map, marker;

function initSignageMap() {
  const mapEl = document.getElementById('signage-map');
  if (!mapEl) return;

  // Default center: Philippines
  const defaultCenter = { lat: 14.5995, lng: 120.9842 };

  map = new google.maps.Map(mapEl, {
    center: defaultCenter,
    zoom: 12,
    styles: [
      { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
      { elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
      { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
      { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
      { featureType: 'road.arterial', elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
      { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#c9c9c9' }] },
    ]
  });

  marker = new google.maps.Marker({
    map,
    draggable: true,
    icon: {
      path: google.maps.SymbolPath.CIRCLE,
      scale: 10,
      fillColor: '#111',
      fillOpacity: 1,
      strokeColor: '#fff',
      strokeWeight: 2
    }
  });

  // Click on map to place marker
  map.addListener('click', (e) => {
    placeMarker(e.latLng);
  });

  marker.addListener('dragend', (e) => {
    updateCoords(e.latLng);
    reverseGeocode(e.latLng);
  });

  // Try geolocation
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const latLng = new google.maps.LatLng(pos.coords.latitude, pos.coords.longitude);
        map.setCenter(latLng);
        placeMarker(latLng);
      },
      () => { /* geolocation denied, use default */ }
    );
  }
}

function placeMarker(latLng) {
  marker.setPosition(latLng);
  updateCoords(latLng);
  reverseGeocode(latLng);
}

function updateCoords(latLng) {
  const lat = latLng.lat().toFixed(7);
  const lng = latLng.lng().toFixed(7);
  const latEl = document.getElementById('sign-lat');
  const lngEl = document.getElementById('sign-lng');
  const coordsEl = document.getElementById('map-coords-display');
  if (latEl) latEl.value = lat;
  if (lngEl) lngEl.value = lng;
  if (coordsEl) coordsEl.textContent = `Lat: ${lat}, Lng: ${lng}`;
}

function reverseGeocode(latLng) {
  const geocoder = new google.maps.Geocoder();
  geocoder.geocode({ location: latLng }, (results, status) => {
    if (status === 'OK' && results[0]) {
      const addr = results[0].formatted_address;
      const addrEl = document.getElementById('sign-address');
      const addrDisplay = document.getElementById('map-coords-display');
      if (addrEl) addrEl.value = addr;
      if (addrDisplay) addrDisplay.textContent = addr;
    }
  });
}

// Expose for Google Maps callback — supports both initMap and initSignageMap
window.initSignageMap = initSignageMap;
window.initMap = initSignageMap;
