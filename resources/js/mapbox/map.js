const getNumber = (value, fallback) => {
  const n = Number(value);
  return Number.isFinite(n) ? n : fallback;
};

const createMarkerElement = ({ label, photoUrl, initials, isMe }) => {
  const el = document.createElement('div');
  el.className = `al-map-marker${isMe ? ' al-map-marker--me' : ''}`;

  const circle = document.createElement('div');
  circle.className = 'al-map-marker__circle';

  const safeInitials = (initials || '').trim();
  if (photoUrl) {
    circle.classList.add('al-map-marker__circle--photo');
    circle.style.backgroundImage = `url("${photoUrl}")`;
    circle.textContent = '';
  } else if (safeInitials) {
    circle.textContent = safeInitials.slice(0, 2).toUpperCase();
  } else {
    circle.textContent = (label || '?').trim().slice(0, 1).toUpperCase();
  }

  el.appendChild(circle);
  return el;
};

const animateMarker = (marker, fromLngLat, toLngLat, durationMs = 650) => {
  const start = performance.now();

  const frame = (now) => {
    const t = Math.min(1, (now - start) / durationMs);
    const ease = 1 - Math.pow(1 - t, 3);
    const lng = fromLngLat.lng + (toLngLat.lng - fromLngLat.lng) * ease;
    const lat = fromLngLat.lat + (toLngLat.lat - fromLngLat.lat) * ease;
    marker.setLngLat([lng, lat]);
    if (t < 1) requestAnimationFrame(frame);
  };

  requestAnimationFrame(frame);
};

const getInitialsFromName = (name) => {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  if (parts.length === 1) return parts[0][0].toUpperCase();
  return '';
};

const normalizeText = (value) =>
  String(value || '')
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .trim()
    .toLowerCase();

const createSafePlaceElement = ({ label, icon }) => {
  const el = document.createElement('div');
  el.className = 'al-safeplace-marker';
  el.title = label || '';
  const iconClass = icon ? String(icon) : 'mdi-home-map-marker';
  el.innerHTML = `<i class="mdi ${iconClass}"></i>`;
  return el;
};

const bootMapbox = () => {
  const mapEl = document.getElementById('map');
  if (!mapEl) return;

  const loadingEl = document.getElementById('alLoadingScreen');
  const loadingTextEl = document.getElementById('alLoadingText');

  const setLoadingText = (text) => {
    if (!loadingTextEl) return;
    loadingTextEl.textContent = String(text || '');
  };

  const hideLoading = () => {
    if (!loadingEl) return;
    loadingEl.classList.add('al-loading-screen--hidden');
    window.setTimeout(() => {
      try {
        loadingEl.remove();
      } catch {
      }
    }, 340);
  };

  const token = mapEl.dataset.mapboxToken || '';
  const style = mapEl.dataset.mapboxStyle || '';
  const centerLng = getNumber(mapEl.dataset.mapboxCenterLng, -46.6333);
  const centerLat = getNumber(mapEl.dataset.mapboxCenterLat, -23.5505);
  const zoom = getNumber(mapEl.dataset.mapboxZoom, 12);
  const meUserId = mapEl.dataset.meUserId ? String(mapEl.dataset.meUserId) : '';
  const meName = mapEl.dataset.meName || '';
  const mePhoto = mapEl.dataset.mePhoto || '';
  const meInitials = mapEl.dataset.meInitials || '';
  const meLat = mapEl.dataset.meLat ? Number(mapEl.dataset.meLat) : null;
  const meLng = mapEl.dataset.meLng ? Number(mapEl.dataset.meLng) : null;
  const meMarkerId = meUserId ? `me:${meUserId}` : '';

  setLoadingText('Carregando…');

  if (!token) {
    setLoadingText('Configure o token do Mapbox para carregar o mapa.');
    return;
  }

  if (typeof window.mapboxgl === 'undefined') {
    return 'retry';
  }

  window.mapboxgl.accessToken = token;

  setLoadingText('Carregando mapa…');

  const initialCenterLng = Number.isFinite(meLng) ? meLng : centerLng;
  const initialCenterLat = Number.isFinite(meLat) ? meLat : centerLat;
  const initialZoom = Number.isFinite(meLat) && Number.isFinite(meLng) ? Math.max(zoom, 15) : zoom;

  const map = new window.mapboxgl.Map({
    container: mapEl,
    style,
    center: [initialCenterLng, initialCenterLat],
    zoom: initialZoom,
  });

  const markers = {};
  let lastDevices = [];
  let pickOnce = null;
  const safePlaces = [];
  const safePlaceMarkers = {};
  let focusedSafePlaceId = null;

  const detailEl = document.getElementById('alMapDetail');
  const detailAvatarEl = document.getElementById('alMapDetailAvatar');
  const detailTitleEl = document.getElementById('alMapDetailTitle');
  const detailSubtitleEl = document.getElementById('alMapDetailSubtitle');
  const detailMetaEl = document.getElementById('alMapDetailMeta');
  const detailActionsEl = document.getElementById('alMapDetailActions');
  const detailCloseEl = document.getElementById('alMapDetailClose');

  const focusSourceId = 'al-safeplace-focus';
  const focusFillId = 'al-safeplace-focus-fill';
  const focusLineId = 'al-safeplace-focus-line';

  const formatLastSeen = (iso) => {
    if (!iso) return '';
    const d = new Date(String(iso));
    if (Number.isNaN(d.getTime())) return '';
    return new Intl.DateTimeFormat('pt-BR', {
      dateStyle: 'short',
      timeStyle: 'short',
    }).format(d);
  };

  const clearEl = (el) => {
    if (!el) return;
    while (el.firstChild) el.removeChild(el.firstChild);
  };

  const setDetailAvatar = ({ photoUrl, initials, iconClass }) => {
    if (!detailAvatarEl) return;
    clearEl(detailAvatarEl);

    if (photoUrl) {
      const img = document.createElement('img');
      img.src = String(photoUrl);
      img.alt = '';
      detailAvatarEl.appendChild(img);
      return;
    }

    if (iconClass) {
      const i = document.createElement('i');
      i.className = `mdi ${String(iconClass)}`;
      detailAvatarEl.appendChild(i);
      return;
    }

    detailAvatarEl.textContent = String(initials || '').slice(0, 2).toUpperCase();
  };

  const hideDetail = () => {
    if (!detailEl) return;
    detailEl.classList.remove('al-map-detail--show');
    detailEl.setAttribute('aria-hidden', 'true');
  };

  const showDetail = () => {
    if (!detailEl) return;
    detailEl.classList.add('al-map-detail--show');
    detailEl.setAttribute('aria-hidden', 'false');
  };

  detailCloseEl?.addEventListener('click', () => hideDetail());

  const ensureFocusLayers = () => {
    if (!map.getSource(focusSourceId)) {
      map.addSource(focusSourceId, {
        type: 'geojson',
        data: { type: 'FeatureCollection', features: [] },
      });
    }

    if (!map.getLayer(focusFillId)) {
      map.addLayer({
        id: focusFillId,
        type: 'fill',
        source: focusSourceId,
        paint: {
          'fill-color': '#0A84FF',
          'fill-opacity': 0.12,
        },
      });
    }

    if (!map.getLayer(focusLineId)) {
      map.addLayer({
        id: focusLineId,
        type: 'line',
        source: focusSourceId,
        paint: {
          'line-color': '#0A84FF',
          'line-opacity': 0.5,
          'line-width': 2,
        },
      });
    }
  };

  const clearFocusedSafePlace = () => {
    focusedSafePlaceId = null;
    try {
      const src = map.getSource(focusSourceId);
      src?.setData({ type: 'FeatureCollection', features: [] });
    } catch {
    }
  };

  const flyToLngLat = (lng, lat, zoomLevel = 16) => {
    if (!Number.isFinite(Number(lng)) || !Number.isFinite(Number(lat))) return;
    map.flyTo({
      center: [Number(lng), Number(lat)],
      zoom: Math.max(Number(zoomLevel) || 16, map.getZoom()),
      essential: true,
    });
  };

  const fitToPoints = (points) => {
    const list = (points || []).filter((p) => Number.isFinite(Number(p?.lng)) && Number.isFinite(Number(p?.lat)));
    if (!list.length) return;

    const bounds = new window.mapboxgl.LngLatBounds([list[0].lng, list[0].lat], [list[0].lng, list[0].lat]);
    list.slice(1).forEach((p) => bounds.extend([Number(p.lng), Number(p.lat)]));

    map.fitBounds(bounds, {
      padding: { top: 170, right: 70, bottom: 120, left: 70 },
      maxZoom: 16,
      duration: 650,
      essential: true,
    });
  };

  const renderMetaLines = (lines) => {
    if (!detailMetaEl) return;
    clearEl(detailMetaEl);
    (lines || []).filter(Boolean).forEach((t) => {
      const line = document.createElement('div');
      line.className = 'al-map-detail__line';
      line.textContent = String(t);
      detailMetaEl.appendChild(line);
    });
  };

  const renderActions = (actions) => {
    if (!detailActionsEl) return;
    clearEl(detailActionsEl);

    (actions || []).forEach((a) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = a?.variant === 'secondary' ? 'al-btn-secondary' : 'al-btn-primary';
      btn.textContent = String(a?.label || '');
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        a?.onClick?.();
      });
      detailActionsEl.appendChild(btn);
    });
  };

  const openPersonDetail = (d, { fly = false } = {}) => {
    if (!d) return;
    clearFocusedSafePlace();

    const name = String(d?.name || '').trim() || 'Pessoa';
    const deviceName = String(d?.device_name || '').trim();
    const photoUrl = d?.photo ? String(d.photo) : '';
    const initials = getInitialsFromName(name);
    const lastSeen = formatLastSeen(d?.last_seen_at);
    const isOnline = Boolean(d?.is_online);
    const lat = Number(d?.lat);
    const lng = Number(d?.lng);

    if (detailTitleEl) detailTitleEl.textContent = name;
    if (detailSubtitleEl) {
      detailSubtitleEl.textContent = isOnline ? 'Online agora' : lastSeen ? `Última localização: ${lastSeen}` : 'Sem atualização recente';
    }
    setDetailAvatar({ photoUrl, initials, iconClass: '' });

    const lines = [];
    if (deviceName) lines.push(`Dispositivo: ${deviceName}`);
    if (lastSeen) lines.push(`Atualizado: ${lastSeen}`);
    if (Number.isFinite(lat) && Number.isFinite(lng)) lines.push(`Coordenadas: ${lat.toFixed(5)}, ${lng.toFixed(5)}`);
    renderMetaLines(lines);

    const actions = [];
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
      actions.push({
        label: 'Ir no mapa',
        variant: 'primary',
        onClick: () => flyToLngLat(lng, lat, 16),
      });
    }
    actions.push({
      label: 'Fechar',
      variant: 'secondary',
      onClick: () => hideDetail(),
    });
    renderActions(actions);

    if (fly && Number.isFinite(lat) && Number.isFinite(lng)) flyToLngLat(lng, lat, 16);
    showDetail();
  };

  const openSafePlaceDetail = (p, { fly = false } = {}) => {
    if (!p) return;

    const id = p?.id ? String(p.id) : '';
    const name = String(p?.name || '').trim() || 'Local seguro';
    const iconClass = p?.icon ? String(p.icon) : 'mdi-home-map-marker';
    const address = String(p?.address || '').trim();
    const radius = Number(p?.radius);
    const lat = Number(p?.lat);
    const lng = Number(p?.lng);

    if (Number.isFinite(lat) && Number.isFinite(lng) && Number.isFinite(radius) && radius > 0) {
      ensureFocusLayers();
      focusedSafePlaceId = id || 'focus';
      try {
        const src = map.getSource(focusSourceId);
        const poly = createCirclePolygon({
          lng,
          lat,
          radiusMeters: Math.max(25, radius),
        });
        src?.setData({ type: 'FeatureCollection', features: [poly] });
      } catch {
      }
    }

    if (detailTitleEl) detailTitleEl.textContent = name;
    if (detailSubtitleEl) detailSubtitleEl.textContent = 'Local seguro';
    setDetailAvatar({ photoUrl: '', initials: '', iconClass });

    const lines = [];
    if (address) lines.push(address);
    if (Number.isFinite(radius) && radius > 0) lines.push(`Raio seguro: ${Math.round(radius)} m`);
    if (Number.isFinite(lat) && Number.isFinite(lng)) lines.push(`Coordenadas: ${lat.toFixed(5)}, ${lng.toFixed(5)}`);
    renderMetaLines(lines);

    const actions = [];
    if (Number.isFinite(lat) && Number.isFinite(lng)) {
      actions.push({
        label: 'Ir no mapa',
        variant: 'primary',
        onClick: () => flyToLngLat(lng, lat, 16),
      });
    }
    actions.push({
      label: focusedSafePlaceId ? 'Ocultar área' : 'Mostrar área',
      variant: 'secondary',
      onClick: () => {
        if (focusedSafePlaceId) clearFocusedSafePlace();
        else openSafePlaceDetail(p, { fly: false });
      },
    });
    renderActions(actions);

    if (fly && Number.isFinite(lat) && Number.isFinite(lng)) flyToLngLat(lng, lat, 16);
    showDetail();
  };

  const openGroupDetail = ({ kind, name, memberIds }) => {
    const title = String(name || '').trim() || (kind === 'circle' ? 'Círculo' : 'Família');
    const ids = Array.isArray(memberIds) ? memberIds.map((x) => String(x)).filter(Boolean) : [];
    const members = lastDevices.filter((d) => d?.user_id && ids.includes(String(d.user_id)));

    if (!members.length) {
      if (detailTitleEl) detailTitleEl.textContent = title;
      if (detailSubtitleEl) detailSubtitleEl.textContent = 'Sem localização disponível';
      setDetailAvatar({ photoUrl: '', initials: '', iconClass: kind === 'circle' ? 'mdi-circle-outline' : 'mdi-account-group-outline' });
      renderMetaLines([]);
      renderActions([
        { label: 'Fechar', variant: 'secondary', onClick: () => hideDetail() },
      ]);
      showDetail();
      return;
    }

    const points = members
      .map((d) => ({ lng: Number(d?.lng), lat: Number(d?.lat) }))
      .filter((p) => Number.isFinite(p.lng) && Number.isFinite(p.lat));

    if (detailTitleEl) detailTitleEl.textContent = title;
    if (detailSubtitleEl) detailSubtitleEl.textContent = `${members.length} pessoa(s) com localização`;
    setDetailAvatar({ photoUrl: '', initials: '', iconClass: kind === 'circle' ? 'mdi-circle-outline' : 'mdi-account-group-outline' });

    renderMetaLines(members.slice(0, 6).map((d) => String(d?.name || '').trim()).filter(Boolean));
    renderActions([
      {
        label: 'Ver no mapa',
        variant: 'primary',
        onClick: () => fitToPoints(points),
      },
      {
        label: 'Fechar',
        variant: 'secondary',
        onClick: () => hideDetail(),
      },
    ]);

    fitToPoints(points);
    showDetail();
  };

  const resolveDeviceByMarkerId = (markerId) => {
    const id = String(markerId || '');
    if (!id) return null;
    if (id.startsWith('me:') && meUserId) return lastDevices.find((d) => d?.user_id && String(d.user_id) === String(meUserId)) || null;
    return lastDevices.find((d) => d?.id && String(d.id) === id) || null;
  };

  const addMarker = (lat, lng, payload, id) => {
    const element = createMarkerElement(payload);
    element.title = payload?.label || '';
    element.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const d = resolveDeviceByMarkerId(id);
      if (d) {
        openPersonDetail(d, { fly: false });
        return;
      }
      const pos = markers[id]?.lngLat;
      openPersonDetail(
        {
          name: payload?.label || meName,
          photo: payload?.photoUrl || '',
          device_name: '',
          lat: pos?.lat ?? lat,
          lng: pos?.lng ?? lng,
          last_seen_at: null,
          is_online: false,
        },
        { fly: false },
      );
    });

    const marker = new window.mapboxgl.Marker({ element, anchor: 'center' })
      .setLngLat([lng, lat])
      .addTo(map);

    markers[id] = {
      marker,
      lngLat: { lng, lat },
      payload,
    };
  };

  const updateMarker = (id, lat, lng) => {
    const entry = markers[id];
    if (!entry) return;

    const fromLngLat = entry.lngLat;
    const toLngLat = { lng, lat };
    entry.lngLat = toLngLat;
    animateMarker(entry.marker, fromLngLat, toLngLat);
  };

  map.on('click', (e) => {
    if (!pickOnce) return;
    const cb = pickOnce;
    pickOnce = null;
    cb(e.lngLat);
  });

  const setMeMarker = (lat, lng) => {
    if (!meMarkerId) return;

    const payload = { label: meName, photoUrl: mePhoto, initials: meInitials, isMe: true };

    if (!markers[meMarkerId]) {
      addMarker(lat, lng, payload, meMarkerId);
      return;
    }

    updateMarker(meMarkerId, lat, lng);
  };

  const syncSafePlaces = (items) => {
    const alive = new Set();

    (items || []).forEach((p) => {
      const id = p?.id ? String(p.id) : '';
      const name = String(p?.name || '').trim();
      const icon = p?.icon ? String(p.icon) : '';
      const lat = Number(p?.lat);
      const lng = Number(p?.lng);

      if (!id || !name || !Number.isFinite(lat) || !Number.isFinite(lng)) return;
      alive.add(id);

      if (!safePlaceMarkers[id]) {
        const element = createSafePlaceElement({ label: name, icon });
        const marker = new window.mapboxgl.Marker({ element, anchor: 'bottom' })
          .setLngLat([lng, lat])
          .addTo(map);
        safePlaceMarkers[id] = marker;
      } else {
        safePlaceMarkers[id].setLngLat([lng, lat]);
      }

      const el = safePlaceMarkers[id]?.getElement?.();
      if (el) {
        el.dataset.safePlaceId = id;
        if (!el.dataset.alClickBound) {
          el.dataset.alClickBound = '1';
          el.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const safeId = el.dataset.safePlaceId ? String(el.dataset.safePlaceId) : '';
            const found = safePlaces.find((sp) => sp?.id && String(sp.id) === safeId);
            if (found) openSafePlaceDetail(found, { fly: false });
          });
        }
      }
    });

    Object.keys(safePlaceMarkers).forEach((id) => {
      if (!alive.has(id)) {
        safePlaceMarkers[id].remove();
        delete safePlaceMarkers[id];
      }
    });
  };

  const createEdgeLayer = () => {
    const host = document.querySelector('.al-map-shell') || mapEl.parentElement || document.body;
    const el = document.createElement('div');
    el.className = 'al-edge-layer';
    host.appendChild(el);
    return el;
  };

  const edgeLayer = createEdgeLayer();

  const makeEdgeItem = ({ key, type, label, iconClass, initials, onClick }) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `al-edge-item al-edge-item--${type}`;
    btn.setAttribute('aria-label', label);
    btn.title = label;

    if (iconClass) {
      const i = document.createElement('i');
      i.className = `mdi ${iconClass}`;
      btn.appendChild(i);
    } else {
      btn.textContent = (initials || '').slice(0, 2).toUpperCase();
    }

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      onClick?.();
    });

    btn.dataset.key = key;
    return btn;
  };

  const updateEdgeItems = () => {
    if (!edgeLayer) return;

    const bounds = map.getBounds();
    const canvas = map.getCanvas();
    const w = canvas.clientWidth;
    const h = canvas.clientHeight;
    const padding = 16;
    const center = map.getCenter();

    const candidates = [];

    const addCandidate = ({ key, lng, lat, type, label, iconClass, initials, onClick }) => {
      if (!Number.isFinite(lng) || !Number.isFinite(lat)) return;
      const inside = bounds.contains([lng, lat]);
      if (inside) return;
      const dx = lng - center.lng;
      const dy = lat - center.lat;
      const score = dx * dx + dy * dy;
      candidates.push({ key, lng, lat, type, label, iconClass, initials, onClick, score });
    };

    if (meMarkerId && markers[meMarkerId]) {
      const pos = markers[meMarkerId].lngLat;
      addCandidate({
        key: meMarkerId,
        lng: pos.lng,
        lat: pos.lat,
        type: 'me',
        label: 'Minha localização',
        iconClass: 'mdi-crosshairs-gps',
        initials: '',
        onClick: () => {
          const d = resolveDeviceByMarkerId(meMarkerId);
          if (d) openPersonDetail(d, { fly: true });
          else focusMe();
        },
      });
    }

    lastDevices.forEach((d) => {
      const id = d?.id ? String(d.id) : '';
      const name = String(d?.name || '').trim();
      const userId = d?.user_id ? String(d.user_id) : '';
      if (!id || !name) return;
      if (meUserId && userId && userId === meUserId) return;

      const lat = Number(d?.lat);
      const lng = Number(d?.lng);
      addCandidate({
        key: `dev:${id}`,
        lng,
        lat,
        type: 'person',
        label: name,
        iconClass: '',
        initials: getInitialsFromName(name),
        onClick: () => openPersonDetail(d, { fly: true }),
      });
    });

    safePlaces.forEach((p) => {
      const id = p?.id ? String(p.id) : '';
      const name = String(p?.name || '').trim();
      const lat = Number(p?.lat);
      const lng = Number(p?.lng);
      if (!id || !name) return;
      addCandidate({
        key: `sp:${id}`,
        lng,
        lat,
        type: 'safe',
        label: name,
        iconClass: p?.icon ? String(p.icon) : 'mdi-home-map-marker',
        initials: '',
        onClick: () => openSafePlaceDetail(p, { fly: true }),
      });
    });

    candidates.sort((a, b) => b.score - a.score);
    const top = candidates.slice(0, 14);

    const alive = new Set(top.map((x) => x.key));
    [...edgeLayer.querySelectorAll('.al-edge-item')].forEach((el) => {
      const k = el.dataset.key;
      if (!k || !alive.has(k)) el.remove();
    });

    top.forEach((c) => {
      let el = edgeLayer.querySelector(`.al-edge-item[data-key="${CSS.escape(c.key)}"]`);
      if (!el) {
        el = makeEdgeItem({
          key: c.key,
          type: c.type,
          label: c.label,
          iconClass: c.iconClass,
          initials: c.initials,
          onClick: c.onClick,
        });
        edgeLayer.appendChild(el);
      }

      const pt = map.project([c.lng, c.lat]);
      const cx = w / 2;
      const cy = h / 2;
      const vx = pt.x - cx;
      const vy = pt.y - cy;
      const maxX = cx - padding;
      const maxY = cy - padding;
      const s = 1 / Math.max(Math.abs(vx) / maxX, Math.abs(vy) / maxY, 1);
      const x = cx + vx * s;
      const y = cy + vy * s;

      el.style.left = `${Math.max(padding, Math.min(w - padding, x))}px`;
      el.style.top = `${Math.max(padding, Math.min(h - padding, y))}px`;
    });
  };

  const fetchSafePlaces = async () => {
    const res = await fetch('/safe-places', {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!res.ok) throw new Error('safe_places_failed');
    return res.json();
  };

  const refreshSafePlaces = async () => {
    try {
      const data = await fetchSafePlaces();
      const list = Array.isArray(data?.safe_places) ? data.safe_places : [];
      safePlaces.splice(0, safePlaces.length, ...list);
      syncSafePlaces(safePlaces);
      updateEdgeItems();
    } catch {
    }
  };

  const syncMarkers = (devices) => {
    const alive = new Set();

    (devices || []).forEach((d) => {
      const id = d?.id;
      const name = d?.name || '';
      const photo = d?.photo || '';
      const userId = d?.user_id ? String(d.user_id) : '';
      const lat = Number(d?.lat);
      const lng = Number(d?.lng);

      if (!id || !Number.isFinite(lat) || !Number.isFinite(lng)) return;
      alive.add(String(id));

      const isMe = meUserId !== '' && userId !== '' && userId === meUserId;
      const payload = {
        label: name,
        photoUrl: isMe ? mePhoto : photo,
        initials: isMe ? meInitials : getInitialsFromName(name),
        isMe,
      };

      if (!markers[String(id)]) addMarker(lat, lng, payload, String(id));
      else updateMarker(String(id), lat, lng);
    });

    if (meMarkerId && markers[meMarkerId]) {
      alive.add(meMarkerId);
    }

    Object.keys(markers).forEach((id) => {
      if (id.startsWith('me:')) return;
      if (!alive.has(id)) {
        markers[id].marker.remove();
        delete markers[id];
      }
    });
  };

  const fetchLocations = async () => {
    const res = await fetch('/api/devices/locations', {
      method: 'GET',
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });

    if (!res.ok) throw new Error('locations_failed');
    return res.json();
  };

  const refresh = async () => {
    try {
      const data = await fetchLocations();
      lastDevices = Array.isArray(data?.devices) ? data.devices : [];
      syncMarkers(lastDevices);
      updateEdgeItems();
    } catch {
    }
  };

  const upsertMeMarker = (forceGeolocate = false) => {
    if (!meUserId) return;

    if (meUserId && Number.isFinite(meLat) && Number.isFinite(meLng)) {
      setMeMarker(meLat, meLng);
      return;
    }

    if (!navigator.geolocation) return;

    const allowed = localStorage.getItem('airlink_location_allowed') === '1';
    if (!allowed && !forceGeolocate) return;

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        localStorage.setItem('airlink_location_allowed', '1');
        setMeMarker(lat, lng);
      },
      () => {},
      { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 },
    );
  };

  const attachSearch = () => {
    const input = document.querySelector('.al-hub__search-input');
    const wrapper = input?.closest('.al-hub__search');
    if (!input || !wrapper) return;

    const hub = document.querySelector('.al-hub');

    const list = document.createElement('div');
    list.className = 'al-hub__results al-card';
    list.style.display = 'none';
    wrapper.appendChild(list);

    const clear = () => {
      list.innerHTML = '';
      list.style.display = 'none';
    };

    const render = ({ people, places, savedPlaces, recentSearches, recentItems, quickPeople, quickSafePlaces }) => {
      list.innerHTML = '';

      const any =
        (people.length > 0) ||
        (places.length > 0) ||
        (savedPlaces.length > 0) ||
        (recentSearches.length > 0) ||
        (recentItems.length > 0) ||
        (quickPeople.length > 0) ||
        (quickSafePlaces.length > 0);
      if (!any) return clear();

      const addSection = (title) => {
        const h = document.createElement('div');
        h.className = 'al-hub__results-title';
        h.textContent = title;
        list.appendChild(h);
      };

      const addButton = ({ left, title, subtitle, onClick }) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'al-hub__result';

        const inner = document.createElement('div');
        inner.className = 'al-hub__result-inner';

        const main = document.createElement('div');
        main.className = 'al-hub__result-main';

        const t = document.createElement('div');
        t.className = 'al-hub__result-title';
        t.textContent = title || '';

        const s = document.createElement('div');
        s.className = 'al-hub__result-subtitle';
        s.textContent = subtitle || '';

        main.appendChild(t);
        if (subtitle) main.appendChild(s);

        if (left) {
          const leftEl = document.createElement('div');
          leftEl.className = 'al-hub__result-left';
          leftEl.appendChild(left);
          inner.appendChild(leftEl);
        }
        inner.appendChild(main);
        btn.appendChild(inner);

        btn.addEventListener('click', () => {
          clear();
          input.value = title || '';
          onClick?.();
        });

        list.appendChild(btn);
      };

      const addChipRow = ({ title, items, onClick }) => {
        if (!items.length) return;
        addSection(title);
        const row = document.createElement('div');
        row.className = 'al-hub__chip-row';
        items.forEach((it) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'al-hub__chip';
          btn.textContent = it.label;
          btn.addEventListener('click', () => {
            clear();
            input.value = it.label;
            onClick(it);
          });
          row.appendChild(btn);
        });
        list.appendChild(row);
      };

      if (recentItems.length) {
        addSection('Recentes');
        recentItems.slice(0, 8).forEach((it) => {
          addButton({
            left: it.type === 'person' ? (() => {
              const icon = document.createElement('i');
              icon.className = 'mdi mdi-account-circle-outline';
              return icon;
            })() : null,
            title: it.label,
            subtitle: it.type === 'person' ? 'Pessoa' : it.type === 'safe_place' ? 'Local seguro' : 'Local',
            onClick: () => it.onClick?.(),
          });
        });
      }

      addChipRow({
        title: 'Atalhos',
        items: quickSafePlaces,
        onClick: (it) => it.onClick?.(),
      });

      if (quickPeople.length) {
        addSection('Pessoas');
        const row = document.createElement('div');
        row.className = 'al-hub__people-row';
        quickPeople.slice(0, 10).forEach((p) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'al-hub__person';
          btn.title = p.name;
          btn.textContent = p.initials;
          btn.addEventListener('click', () => {
            clear();
            input.value = p.name;
            p.onClick?.();
          });
          row.appendChild(btn);
        });
        list.appendChild(row);
      }

      if (recentSearches.length) {
        addSection('Últimas buscas');
        recentSearches.slice(0, 6).forEach((q) => {
          addButton({
            left: (() => {
              const icon = document.createElement('i');
              icon.className = 'mdi mdi-history';
              return icon;
            })(),
            title: q,
            subtitle: '',
            onClick: () => {
              input.value = q;
              search();
            },
          });
        });
      }

      if (people.length) {
        addSection('Pessoas');
        people.forEach((p) => {
          const icon = document.createElement('i');
          icon.className = p.isMe ? 'mdi mdi-account-circle' : 'mdi mdi-account-circle-outline';

          addButton({
            left: icon,
            title: p.name,
            subtitle: p.isMe ? 'Você' : 'Pessoa',
            onClick: () => {
              const entry = markers[p.markerId];
              if (!entry) return;
              pushRecentItem({
                type: 'person',
                key: `p:${p.markerId}`,
                label: p.name,
                data: { markerId: p.markerId },
                onClick: () => map.flyTo({ center: [entry.lngLat.lng, entry.lngLat.lat], zoom: 16, essential: true }),
              });
              map.flyTo({ center: [entry.lngLat.lng, entry.lngLat.lat], zoom: 16, essential: true });
            },
          });
        });
      }

      if (places.length) {
        addSection('Locais');
        places.forEach((f) => {
          const title = f.place_name || f.text || '';

          addButton({
            title,
            subtitle: 'Local',
            onClick: () => {
              const c = f.center;
              if (!Array.isArray(c) || c.length !== 2) return;
              pushRecentItem({
                type: 'place',
                key: `pl:${String(title)}`,
                label: title,
                data: { center: c },
                onClick: () => map.flyTo({ center: c, zoom: 15, essential: true }),
              });
              map.flyTo({ center: c, zoom: 15, essential: true });
            },
          });
        });
      }

      if (savedPlaces.length) {
        addSection('Locais seguros');
        savedPlaces.forEach((p) => {
          addButton({
            title: p.name,
            subtitle: 'Local seguro',
            onClick: () => {
              pushRecentItem({
                type: 'safe_place',
                key: `sp:${p.id}`,
                label: p.name,
                data: { id: p.id, lat: p.lat, lng: p.lng },
                onClick: () => map.flyTo({ center: [p.lng, p.lat], zoom: 16, essential: true }),
              });
              map.flyTo({ center: [p.lng, p.lat], zoom: 16, essential: true });
            },
          });
        });
      }

      list.style.display = 'block';
    };

    let debounce = null;

    const recSearchKey = 'airlink_recent_searches';
    const recItemsKey = 'airlink_recent_items';

    const loadRecentSearches = () => {
      try {
        const v = JSON.parse(localStorage.getItem(recSearchKey) || '[]');
        return Array.isArray(v) ? v.filter((x) => typeof x === 'string') : [];
      } catch {
        return [];
      }
    };

    const saveRecentSearch = (q) => {
      const value = String(q || '').trim();
      if (!value) return;
      const cur = loadRecentSearches().filter((x) => x !== value);
      cur.unshift(value);
      localStorage.setItem(recSearchKey, JSON.stringify(cur.slice(0, 12)));
    };

    const loadRecentItems = () => {
      try {
        const v = JSON.parse(localStorage.getItem(recItemsKey) || '[]');
        return Array.isArray(v) ? v : [];
      } catch {
        return [];
      }
    };

    const pushRecentItem = ({ type, key, label, data, onClick }) => {
      const now = Date.now();
      const cur = loadRecentItems().filter((x) => x?.key !== key);
      cur.unshift({ type, key, label, ts: now, data: data || null });
      localStorage.setItem(recItemsKey, JSON.stringify(cur.slice(0, 20)));
      pushRecentItem.handlers[key] = onClick;
    };
    pushRecentItem.handlers = {};

    const getRecentItems = () =>
      loadRecentItems().map((it) => {
        const key = String(it?.key || '');
        const type = String(it?.type || '');
        const data = it?.data || null;

        const handler = pushRecentItem.handlers[key];
        if (typeof handler === 'function') {
          return { ...it, onClick: handler };
        }

        if (type === 'place' && data && Array.isArray(data.center) && data.center.length === 2) {
          return {
            ...it,
            onClick: () => map.flyTo({ center: data.center, zoom: 15, essential: true }),
          };
        }

        if (type === 'safe_place' && data && Number.isFinite(Number(data.lat)) && Number.isFinite(Number(data.lng))) {
          return {
            ...it,
            onClick: () => map.flyTo({ center: [Number(data.lng), Number(data.lat)], zoom: 16, essential: true }),
          };
        }

        if (type === 'person' && data && data.markerId) {
          return {
            ...it,
            onClick: () => {
              const entry = markers[String(data.markerId)];
              if (!entry) return;
              map.flyTo({ center: [entry.lngLat.lng, entry.lngLat.lat], zoom: 16, essential: true });
            },
          };
        }

        return { ...it, onClick: null };
      });

    const showSheet = () => {
      hub?.classList.add('al-hub--searching');

      const recentSearches = loadRecentSearches();
      const recentItems = getRecentItems().filter((it) => typeof it.onClick === 'function');

      const recentIndex = new Map(recentItems.map((it) => [it.key, Number(it.ts || 0)]));

      const quickSafePlaces = safePlaces
        .filter((p) => {
          const n = normalizeText(p?.name);
          return n === 'casa' || n === 'trabalho' || n === 'faculdade' || n === 'escola';
        })
        .map((p) => ({
          label: p.name,
          onClick: () => {
            pushRecentItem({
              type: 'safe_place',
              key: `sp:${p.id}`,
              label: p.name,
              data: { id: p.id, lat: Number(p.lat), lng: Number(p.lng) },
            });
            map.flyTo({ center: [Number(p.lng), Number(p.lat)], zoom: 16, essential: true });
          },
          _score: recentIndex.get(`sp:${p.id}`) || 0,
        }));

      const quickPeople = lastDevices
        .map((d) => {
          const id = d?.id ? String(d.id) : '';
          const name = String(d?.name || '').trim();
          const lat = Number(d?.lat);
          const lng = Number(d?.lng);
          if (!id || !name || !Number.isFinite(lat) || !Number.isFinite(lng)) return null;
          return {
            label: name,
            name,
            initials: getInitialsFromName(name),
            onClick: () => {
              pushRecentItem({
                type: 'person',
                key: `p:${id}`,
                label: name,
                data: { markerId: id },
              });
              map.flyTo({ center: [lng, lat], zoom: 16, essential: true });
            },
            _score: recentIndex.get(`p:${id}`) || 0,
          };
        })
        .filter(Boolean);

      quickPeople.sort((a, b) => (b._score || 0) - (a._score || 0));
      quickSafePlaces.sort((a, b) => (b._score || 0) - (a._score || 0));

      render({
        people: [],
        places: [],
        savedPlaces: [],
        recentSearches,
        recentItems,
        quickPeople,
        quickSafePlaces,
      });
    };

    const search = async () => {
      const q = (input.value || '').trim();
      if (q.length < 1) return showSheet();
      if (q.length < 3) return;

      saveRecentSearch(q);

      const people = lastDevices
        .map((d) => {
          const id = d?.id ? String(d.id) : '';
          const name = String(d?.name || '').trim();
          const photoUrl = String(d?.photo || '').trim();
          const userId = d?.user_id ? String(d.user_id) : '';
          const isMe = meUserId && userId && userId === meUserId;

          if (!id || !name) return null;
          return {
            markerId: id,
            name,
            photoUrl: isMe ? mePhoto : photoUrl,
            initials: isMe ? meInitials : getInitialsFromName(name),
            isMe,
          };
        })
        .filter(Boolean)
        .filter((p) => p.name.toLowerCase().includes(q.toLowerCase()))
        .slice(0, 5);

      const savedPlaces = safePlaces
        .map((p) => ({
          id: p?.id ? String(p.id) : '',
          name: String(p?.name || '').trim(),
          lat: Number(p?.lat),
          lng: Number(p?.lng),
        }))
        .filter((p) => p.id && p.name && Number.isFinite(p.lat) && Number.isFinite(p.lng))
        .filter((p) => p.name.toLowerCase().includes(q.toLowerCase()))
        .slice(0, 4);

      const proximity = map.getCenter();
      const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(q)}.json`);
      url.searchParams.set('access_token', token);
      url.searchParams.set('autocomplete', 'true');
      url.searchParams.set('limit', '6');
      url.searchParams.set('language', 'pt');
      url.searchParams.set('country', 'BR');
      url.searchParams.set('proximity', `${proximity.lng},${proximity.lat}`);
      url.searchParams.set('types', 'address,poi,place,neighborhood,locality');

      const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
      if (!res.ok) return render({ people, places: [], savedPlaces, recentSearches: [], recentItems: [], quickPeople: [], quickSafePlaces: [] });

      const data = await res.json();
      const features = Array.isArray(data?.features) ? data.features : [];
      hub?.classList.add('al-hub--searching');
      render({ people, places: features, savedPlaces, recentSearches: [], recentItems: [], quickPeople: [], quickSafePlaces: [] });
    };

    input.addEventListener('input', () => {
      if (debounce) window.clearTimeout(debounce);
      debounce = window.setTimeout(() => search(), 240);
    });

    input.addEventListener('focus', () => {
      if (hub?.classList.contains('al-hub--collapsed')) {
        hub.classList.remove('al-hub--collapsed');
        localStorage.setItem('airlink_hub_collapsed', '0');
      }
      showSheet();
    });

    input.addEventListener('keydown', (ev) => {
      if (ev.key === 'Escape') {
        clear();
        input.blur();
      }
    });

    input.addEventListener('blur', () => {
      window.setTimeout(() => {
        clear();
        hub?.classList.remove('al-hub--searching');
      }, 220);
    });
  };

  const focusMe = () => {
    const myDevice = lastDevices.find((d) => d?.user_id && String(d.user_id) === meUserId);
    const id = myDevice?.id ? String(myDevice.id) : '';

    if (id && markers[id]) {
      const entry = markers[id];
      map.flyTo({ center: [entry.lngLat.lng, entry.lngLat.lat], zoom: 16, essential: true });
      return;
    }

    if (meMarkerId && markers[meMarkerId]) {
      const entry = markers[meMarkerId];
      map.flyTo({ center: [entry.lngLat.lng, entry.lngLat.lat], zoom: 16, essential: true });
      return;
    }

    upsertMeMarker(true);

    window.setTimeout(() => {
      if (meMarkerId && markers[meMarkerId]) {
        const entry = markers[meMarkerId];
        map.flyTo({ center: [entry.lngLat.lng, entry.lngLat.lat], zoom: 16, essential: true });
      }
    }, 900);
  };

  const attachNavbarFocus = () => {
    const btn = document.getElementById('alFocusMe');
    if (!btn) return;
    btn.addEventListener('click', () => focusMe());
  };

  const attachDashboardActions = () => {
    const host = document.body;
    if (!host) return;

    host.addEventListener('click', (e) => {
      const btn = e.target?.closest?.('[data-al-action]');
      if (!btn) return;

      const action = btn.dataset.alAction ? String(btn.dataset.alAction) : '';
      if (!action) return;

      if (action === 'open-family') {
        const name = btn.dataset.familyName ? String(btn.dataset.familyName) : 'Família';
        const ids = btn.dataset.memberIds ? String(btn.dataset.memberIds) : '';
        const memberIds = ids.split(',').map((x) => x.trim()).filter(Boolean);
        try {
          const modalEl = document.getElementById('familiesModal');
          window.bootstrap?.Modal?.getOrCreateInstance(modalEl)?.hide();
        } catch {
        }
        openGroupDetail({ kind: 'family', name, memberIds });
        return;
      }

      if (action === 'open-circle') {
        const name = btn.dataset.circleName ? String(btn.dataset.circleName) : 'Círculo';
        const ids = btn.dataset.memberIds ? String(btn.dataset.memberIds) : '';
        const memberIds = ids.split(',').map((x) => x.trim()).filter(Boolean);
        try {
          const modalEl = document.getElementById('circlesModal');
          window.bootstrap?.Modal?.getOrCreateInstance(modalEl)?.hide();
        } catch {
        }
        openGroupDetail({ kind: 'circle', name, memberIds });
        return;
      }

      if (action === 'view-safe-place') {
        const p = {
          id: btn.dataset.safePlaceId ? String(btn.dataset.safePlaceId) : '',
          name: btn.dataset.safePlaceName ? String(btn.dataset.safePlaceName) : 'Local seguro',
          icon: btn.dataset.safePlaceIcon ? String(btn.dataset.safePlaceIcon) : 'mdi-home-map-marker',
          address: btn.dataset.safePlaceAddress ? String(btn.dataset.safePlaceAddress) : '',
          lat: btn.dataset.safePlaceLat ? Number(btn.dataset.safePlaceLat) : null,
          lng: btn.dataset.safePlaceLng ? Number(btn.dataset.safePlaceLng) : null,
          radius: btn.dataset.safePlaceRadius ? Number(btn.dataset.safePlaceRadius) : null,
        };
        try {
          const modalEl = document.getElementById('safePlacesModal');
          window.bootstrap?.Modal?.getOrCreateInstance(modalEl)?.hide();
        } catch {
        }
        openSafePlaceDetail(p, { fly: true });
      }
    });
  };

  const attachPickSafePlace = () => {
    const btn = document.getElementById('pickSafePlaceOnMap');
    const latEl = document.getElementById('safePlaceLat');
    const lngEl = document.getElementById('safePlaceLng');
    const hint = document.getElementById('safePlaceHint');
    if (!btn || !latEl || !lngEl) return;

    btn.addEventListener('click', () => {
      if (hint) hint.textContent = 'Clique no mapa para marcar o local.';

      pickOnce = (lngLat) => {
        latEl.value = String(lngLat.lat.toFixed(6));
        lngEl.value = String(lngLat.lng.toFixed(6));
        if (hint) hint.textContent = 'Local selecionado. Agora você pode salvar.';
        map.flyTo({ center: [lngLat.lng, lngLat.lat], zoom: Math.max(map.getZoom(), 16), essential: true });
      };
    });
  };

  const createCirclePolygon = ({ lng, lat, radiusMeters, points = 64 }) => {
    const coords = [];
    const rad = radiusMeters / 1000;
    const latRad = (lat * Math.PI) / 180;
    const lngRad = (lng * Math.PI) / 180;
    const earth = 6371;
    const d = rad / earth;

    for (let i = 0; i <= points; i += 1) {
      const brng = (i * 2 * Math.PI) / points;
      const lat2 = Math.asin(Math.sin(latRad) * Math.cos(d) + Math.cos(latRad) * Math.sin(d) * Math.cos(brng));
      const lng2 =
        lngRad +
        Math.atan2(Math.sin(brng) * Math.sin(d) * Math.cos(latRad), Math.cos(d) - Math.sin(latRad) * Math.sin(lat2));
      coords.push([(lng2 * 180) / Math.PI, (lat2 * 180) / Math.PI]);
    }

    return {
      type: 'Feature',
      geometry: {
        type: 'Polygon',
        coordinates: [coords],
      },
      properties: {},
    };
  };

  let safePlaceDraft = {
    lng: null,
    lat: null,
    name: '',
    icon: 'mdi-home-map-marker',
    radius: 150,
  };
  let safePlaceDraftMarker = null;

  const draftSourceId = 'al-safeplace-draft';
  const draftFillId = 'al-safeplace-draft-fill';
  const draftLineId = 'al-safeplace-draft-line';

  const ensureDraftLayers = () => {
    if (!map.getSource(draftSourceId)) {
      map.addSource(draftSourceId, {
        type: 'geojson',
        data: {
          type: 'FeatureCollection',
          features: [],
        },
      });
    }

    if (!map.getLayer(draftFillId)) {
      map.addLayer({
        id: draftFillId,
        type: 'fill',
        source: draftSourceId,
        paint: {
          'fill-color': '#0A84FF',
          'fill-opacity': 0.14,
        },
      });
    }

    if (!map.getLayer(draftLineId)) {
      map.addLayer({
        id: draftLineId,
        type: 'line',
        source: draftSourceId,
        paint: {
          'line-color': '#0A84FF',
          'line-opacity': 0.55,
          'line-width': 2,
        },
      });
    }
  };

  const setDraftGeo = () => {
    if (!Number.isFinite(safePlaceDraft.lat) || !Number.isFinite(safePlaceDraft.lng)) return;
    ensureDraftLayers();
    const src = map.getSource(draftSourceId);
    if (!src) return;

    const poly = createCirclePolygon({
      lng: safePlaceDraft.lng,
      lat: safePlaceDraft.lat,
      radiusMeters: Math.max(25, Number(safePlaceDraft.radius) || 150),
    });

    src.setData({
      type: 'FeatureCollection',
      features: [poly],
    });
  };

  const setDraftMarker = () => {
    if (!Number.isFinite(safePlaceDraft.lat) || !Number.isFinite(safePlaceDraft.lng)) return;

    const el = document.createElement('div');
    el.className = 'al-safeplace-draft';
    const iconClass = safePlaceDraft.icon ? String(safePlaceDraft.icon) : 'mdi-home-map-marker';
    const name = safePlaceDraft.name ? String(safePlaceDraft.name) : 'Local seguro';
    el.innerHTML = `<i class="mdi ${iconClass}"></i><span>${name}</span>`;

    if (!safePlaceDraftMarker) {
      safePlaceDraftMarker = new window.mapboxgl.Marker({ element: el, anchor: 'bottom' })
        .setLngLat([safePlaceDraft.lng, safePlaceDraft.lat])
        .addTo(map);
    } else {
      try {
        safePlaceDraftMarker.remove();
      } catch {
      }
      safePlaceDraftMarker = new window.mapboxgl.Marker({ element: el, anchor: 'bottom' })
        .setLngLat([safePlaceDraft.lng, safePlaceDraft.lat])
        .addTo(map);
    }
  };

  const updateSafePlaceDraft = () => {
    setDraftGeo();
    setDraftMarker();
  };

  const clearSafePlaceDraft = () => {
    try {
      if (safePlaceDraftMarker) safePlaceDraftMarker.remove();
    } catch {
    }
    safePlaceDraftMarker = null;

    try {
      const src = map.getSource(draftSourceId);
      src?.setData({ type: 'FeatureCollection', features: [] });
    } catch {
    }
  };

  const attachSafePlaceBuilder = () => {
    const modalEl = document.getElementById('safePlacesModal');
    const form = document.getElementById('safePlaceForm');
    const addressInput = document.getElementById('safePlaceAddress');
    const addressResults = document.getElementById('safePlaceAddressResults');
    const latEl = document.getElementById('safePlaceLat');
    const lngEl = document.getElementById('safePlaceLng');
    const radiusEl = document.getElementById('safePlaceRadius');
    const radiusValue = document.getElementById('safePlaceRadiusValue');
    const nameEl = form?.querySelector('input[name="name"]');
    const iconEl = form?.querySelector('select[name="icon"]');

    if (!modalEl || !form || !addressInput || !addressResults || !latEl || !lngEl || !radiusEl || !radiusValue) return;

    let debounce = null;
    let features = [];

    const hideResults = () => {
      addressResults.classList.add('d-none');
      addressResults.innerHTML = '';
      features = [];
    };

    const showResults = () => {
      if (!features.length) return hideResults();
      addressResults.classList.remove('d-none');
    };

    const applyDraftState = () => {
      safePlaceDraft.name = String(nameEl?.value || '').trim();
      safePlaceDraft.icon = String(iconEl?.value || 'mdi-home-map-marker');
      safePlaceDraft.radius = Number(radiusEl.value || 150);
      radiusValue.textContent = String(safePlaceDraft.radius);
      updateSafePlaceDraft();
    };

    const setCenter = ({ lng, lat }) => {
      safePlaceDraft.lng = Number(lng);
      safePlaceDraft.lat = Number(lat);
      latEl.value = String(safePlaceDraft.lat);
      lngEl.value = String(safePlaceDraft.lng);
      map.flyTo({ center: [safePlaceDraft.lng, safePlaceDraft.lat], zoom: Math.max(15, map.getZoom()), essential: true });
      applyDraftState();
    };

    const renderResults = () => {
      addressResults.innerHTML = '';
      features.slice(0, 6).forEach((f) => {
        const title = String(f?.place_name || f?.text || '').trim();
        const c = f?.center;
        if (!title || !Array.isArray(c) || c.length !== 2) return;
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.innerHTML = `<div style="line-height:1.2"><div class="fw-semibold">${title}</div></div>`;
        btn.addEventListener('click', () => {
          addressInput.value = title;
          hideResults();
          setCenter({ lng: Number(c[0]), lat: Number(c[1]) });
        });
        addressResults.appendChild(btn);
      });
      showResults();
    };

    const searchAddress = async () => {
      const q = String(addressInput.value || '').trim();
      if (q.length < 3) return hideResults();

      const url = new URL(`https://api.mapbox.com/geocoding/v5/mapbox.places/${encodeURIComponent(q)}.json`);
      url.searchParams.set('access_token', token);
      url.searchParams.set('limit', '6');
      url.searchParams.set('country', 'BR');
      url.searchParams.set('language', 'pt');
      url.searchParams.set('types', 'address,poi,place,locality,neighborhood');

      const res = await fetch(url.toString(), { headers: { Accept: 'application/json' } });
      if (!res.ok) return hideResults();
      const data = await res.json();
      features = Array.isArray(data?.features) ? data.features : [];
      renderResults();
    };

    addressInput.addEventListener('input', () => {
      if (debounce) window.clearTimeout(debounce);
      debounce = window.setTimeout(() => searchAddress(), 220);
    });

    addressInput.addEventListener('focus', () => {
      if (features.length) showResults();
    });

    addressInput.addEventListener('blur', () => {
      window.setTimeout(() => hideResults(), 220);
    });

    radiusEl.addEventListener('input', () => applyDraftState());
    nameEl?.addEventListener('input', () => applyDraftState());
    iconEl?.addEventListener('change', () => applyDraftState());

    modalEl.addEventListener('hidden.bs.modal', () => {
      hideResults();
      clearSafePlaceDraft();
    });

    form.addEventListener('reset', () => {
      hideResults();
      clearSafePlaceDraft();
    });
  };

  map.on('load', () => {
    upsertMeMarker();
    attachSearch();
    attachNavbarFocus();
    attachDashboardActions();
    attachPickSafePlace();
    attachSafePlaceBuilder();
    setLoadingText('Localizando pontos…');

    (async () => {
      await refresh();
      await refreshSafePlaces();
      if (!Number.isFinite(meLat) || !Number.isFinite(meLng)) {
        window.setTimeout(() => focusMe(), 250);
      }
      hideLoading();
      window.setInterval(refresh, 5000);
    })();
  });

  map.on('move', () => updateEdgeItems());
  map.on('zoom', () => updateEdgeItems());

  window.AirlinkMap = {
    addMarker: (lat, lng, name) => addMarker(lat, lng, { label: name }, `${Date.now()}`),
    updateMarker: (id, lat, lng) => updateMarker(String(id), lat, lng),
    focusMe: () => focusMe(),
    refreshSafePlaces: () => refreshSafePlaces(),
    clearSafePlaceDraft: () => clearSafePlaceDraft(),
    pickPointOnce: (cb) => {
      pickOnce = cb;
    },
  };
};

const bootWithRetry = () => {
  let tries = 0;

  const attempt = () => {
    const result = bootMapbox();
    if (result !== 'retry') return;

    tries += 1;
    if (tries >= 25) return;
    window.setTimeout(attempt, 120);
  };

  attempt();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootWithRetry);
} else {
  bootWithRetry();
}
