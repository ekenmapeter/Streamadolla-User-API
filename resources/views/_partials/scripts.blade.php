<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN': csrfToken};

    // ── Tab Switching ────────────────────────────────────────────────────
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.remove('hidden');
        });
    });

    // ── Assign Form: Platform Toggle ─────────────────────────────────────
    document.querySelectorAll('.assign-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.assign-platform-btn').forEach(b => {
                b.classList.remove('border-primary-500','bg-primary-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-primary-500','bg-primary-50');
            this.classList.remove('border-gray-200');
            const p = this.dataset.platform;
            document.getElementById('assign-spotify-group').classList.toggle('hidden', p !== 'spotify');
            document.getElementById('assign-youtube-group').classList.toggle('hidden', p !== 'youtube');
            document.getElementById('assign-apple_music-group').classList.toggle('hidden', p !== 'apple_music');
        });
    });

    // ── Assign Form: Device selection count & submit button ──────────────
    function updateSelectionCount() {
        const checked = document.querySelectorAll('.device-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = checked;
        document.getElementById('assignSubmitBtn').disabled = checked === 0;
        // Toggle check icon visibility
        document.querySelectorAll('.device-checkbox').forEach(cb => {
            const icon = cb.closest('label').querySelector('.fa-check');
            if (icon) icon.parentElement.classList.toggle('bg-primary-500', cb.checked);
            if (icon) icon.parentElement.classList.toggle('border-primary-500', cb.checked);
            if (icon) icon.classList.toggle('hidden', !cb.checked);
        });
    }
    document.querySelectorAll('.device-checkbox').forEach(cb => cb.addEventListener('change', updateSelectionCount));

    document.getElementById('selectAllDevices')?.addEventListener('click', function() {
        document.querySelectorAll('.device-checkbox:not(:disabled)').forEach(cb => cb.checked = true);
        updateSelectionCount();
    });
    document.getElementById('deselectAllDevices')?.addEventListener('click', function() {
        document.querySelectorAll('.device-checkbox').forEach(cb => cb.checked = false);
        updateSelectionCount();
    });

    // ── Assign Form: Set media_url before submit ─────────────────────────
    document.getElementById('assignForm')?.addEventListener('submit', function(e) {
        const platform = document.querySelector('.assign-platform-btn input:checked')?.value || 'spotify';
        const url = platform === 'spotify'
            ? document.getElementById('assign_media_spotify').value
            : (platform === 'apple_music'
                ? document.getElementById('assign_media_apple_music').value
                : document.getElementById('assign_media_youtube').value);
        if (!url) { e.preventDefault(); showNotification('Please enter a media URL/URI', 'error'); return; }
        document.getElementById('assign_media_url').value = url;
    });

    // ── Broadcast: Platform Toggle ───────────────────────────────────────
    document.querySelectorAll('.bcast-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.bcast-platform-btn').forEach(b => {
                b.classList.remove('border-primary-500','bg-primary-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-primary-500','bg-primary-50');
            this.classList.remove('border-gray-200');
            const p = this.dataset.platform;
            document.getElementById('bcast-spotify-group').classList.toggle('hidden', p !== 'spotify');
            document.getElementById('bcast-youtube-group').classList.toggle('hidden', p !== 'youtube');
            document.getElementById('bcast-apple_music-group').classList.toggle('hidden', p !== 'apple_music');
        });
    });

    // ── Modal: Platform Toggle ───────────────────────────────────────────
    document.querySelectorAll('.modal-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.modal-platform-btn').forEach(b => {
                b.classList.remove('border-primary-500','bg-primary-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-primary-500','bg-primary-50');
            this.classList.remove('border-gray-200');
            const p = this.dataset.platform;
            document.getElementById('modal-spotify-group').classList.toggle('hidden', p !== 'spotify');
            document.getElementById('modal-youtube-group').classList.toggle('hidden', p !== 'youtube');
            document.getElementById('modal-apple_music-group').classList.toggle('hidden', p !== 'apple_music');
            this.querySelector('input').checked = true;
        });
    });

    // ── Single Device: Open Modal ────────────────────────────────────────
    document.querySelectorAll('.send-single').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('modal_device_id').value = this.dataset.deviceId;
            document.getElementById('playDeviceModal').classList.remove('hidden');
        });
    });

    // ── Single Device: Confirm Assignment ────────────────────────────────
    document.getElementById('confirmPlayDevice')?.addEventListener('click', function() {
        const deviceId = document.getElementById('modal_device_id').value;
        const platform = document.querySelector('input[name="modal_platform"]:checked').value;
        const uri = platform === 'spotify'
            ? document.getElementById('modal_spotify_uri').value
            : (platform === 'apple_music'
                ? document.getElementById('modal_apple_music_url').value
                : document.getElementById('modal_youtube_url').value);
        const title = document.getElementById('modal_media_title')?.value || '';

        if (!uri) { showNotification('Please enter a URL/URI', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Assigning...';

        fetch('/api/assignments', {
            method: 'POST', headers: headers,
            body: JSON.stringify({ device_ids: [parseInt(deviceId)], platform: platform, media_url: uri, media_title: title || null })
        })
        .then(r => r.json())
        .then(data => {
            showNotification(data.success ? 'Task assigned to device!' : ('Failed: ' + (data.message || 'Unknown')), data.success ? 'success' : 'error');
            document.getElementById('playDeviceModal').classList.add('hidden');
            this.disabled = false; this.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Assign & Play';
            if (data.success) setTimeout(() => location.reload(), 1000);
        })
        .catch(err => {
            showNotification('Error: ' + err, 'error');
            this.disabled = false; this.innerHTML = '<i class="fas fa-paper-plane mr-2"></i>Assign & Play';
        });
    });

    // ── Assignment Controls (Play/Pause/Stop) ────────────────────────────
    document.querySelectorAll('.assignment-control').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const action = this.dataset.action;
            const endpoint = action === 'stop' ? `/api/assignments/${id}` : `/api/assignments/${id}/control`;
            const method = action === 'stop' ? 'DELETE' : 'POST';
            const body = action === 'stop' ? undefined : JSON.stringify({ action: action });

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            fetch(endpoint, { method: method, headers: headers, body: body })
            .then(r => r.json())
            .then(data => {
                showNotification(data.message || 'Done', data.success ? 'success' : 'error');
                setTimeout(() => location.reload(), 800);
            })
            .catch(err => {
                showNotification('Error: ' + err, 'error');
                this.disabled = false; this.innerHTML = action;
            });
        });
    });

    // ── Manual Register ──────────────────────────────────────────────────
    document.getElementById('submitManualRegister')?.addEventListener('click', function() {
        const name = document.getElementById('manual_name').value;
        const deviceId = document.getElementById('manual_device_id').value;
        const fcmToken = document.getElementById('manual_fcm_token').value;
        if (!deviceId || !fcmToken) { showNotification('Device ID and FCM Token are required', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';

        fetch('/api/devices/register', {
            method: 'POST', headers: headers,
            body: JSON.stringify({ device_id: deviceId, fcm_token: fcmToken, name: name || 'Manual Device', metadata: { type: 'manual_entry' } })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) { showNotification('Device registered!', 'success'); setTimeout(() => location.reload(), 1000); }
            else { showNotification('Failed: ' + (data.message || 'Error'), 'error'); this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Register Device'; }
        })
        .catch(err => { showNotification('Error: ' + err, 'error'); this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Register Device'; });
    });

    // ── Remove Device ────────────────────────────────────────────────────
    document.querySelectorAll('.remove-device').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Remove this device from the dashboard?')) return;
            fetch(`/api/devices/${this.dataset.deviceId}`, { method: 'DELETE', headers: headers })
            .then(r => r.json())
            .then(data => { showNotification(data.message || 'Removed', 'success'); setTimeout(() => location.reload(), 800); })
            .catch(err => showNotification('Error: ' + err, 'error'));
        });
    });

    // ── Edit Device ──────────────────────────────────────────────────────
    document.querySelectorAll('.edit-device').forEach(btn => {
        btn.addEventListener('click', function() {
            const newName = prompt('Enter new phone name:', this.dataset.deviceName);
            if (!newName || newName.trim() === '' || newName === this.dataset.deviceName) return;
            
            fetch(`/api/devices/${this.dataset.deviceId}`, { 
                method: 'PUT', 
                headers: headers,
                body: JSON.stringify({ name: newName.trim() })
            })
            .then(r => r.json())
            .then(data => { 
                if (data.success) {
                    showNotification(data.message || 'Renamed successfully', 'success'); 
                    setTimeout(() => location.reload(), 800); 
                } else {
                    showNotification('Failed to rename', 'error');
                }
            })
            .catch(err => showNotification('Error: ' + err, 'error'));
        });
    });

    // ── Campaign: Platform Toggle ────────────────────────────────────────
    document.querySelectorAll('.campaign-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.campaign-platform-btn').forEach(b => {
                b.classList.remove('border-emerald-500','bg-emerald-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-emerald-500','bg-emerald-50');
            this.classList.remove('border-gray-200');
            this.querySelector('input').checked = true;
            // Show channel URL field only for YouTube
            const isYoutube = this.dataset.platform === 'youtube';
            document.getElementById('campaign-channel-url-group').classList.toggle('hidden', !isYoutube);
        });
    });
    // Show/hide channel URL on page load based on initial platform selection
    document.addEventListener('DOMContentLoaded', function() {
        const initialPlatform = document.querySelector('input[name="campaign_platform"]:checked');
        if (initialPlatform) {
            document.getElementById('campaign-channel-url-group').classList.toggle('hidden', initialPlatform.value !== 'youtube');
        }
    });

    // ── Campaign: Interstitial Toggle ────────────────────────────────────
    document.getElementById('campaign_interstitial_enabled')?.addEventListener('change', function() {
        document.getElementById('campaign-interstitial-fields').classList.toggle('hidden', !this.checked);
    });

    // ── Campaign: Add Track Row ──────────────────────────────────────────
    let trackCount = 1;
    document.getElementById('addTrackBtn')?.addEventListener('click', function() {
        trackCount++;
        const container = document.getElementById('campaignTracksContainer');
        const row = document.createElement('div');
        row.className = 'campaign-track-row flex items-center space-x-2';
        row.innerHTML = `
            <span class="text-xs text-gray-400 font-bold w-5 flex-shrink-0">${trackCount}</span>
            <input type="text" placeholder="URL" class="campaign-track-url flex-1 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
            <input type="text" placeholder="Title" class="campaign-track-title w-24 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500">
            <input type="number" placeholder="180" value="180" min="30" max="7200" class="campaign-track-duration w-16 p-2.5 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-emerald-500" title="Duration in seconds">
            <select class="campaign-track-type w-20 p-2.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-emerald-500 bg-white" title="Track type">
                <option value="playlist">Playlist</option>
                <option value="channel_video">Channel</option>
                <option value="similar_video">Similar</option>
            </select>
            <button type="button" class="remove-track-btn text-red-400 hover:text-red-600 flex-shrink-0"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
        row.querySelector('.remove-track-btn').addEventListener('click', () => { row.remove(); renumberTracks(); });
    });

    function renumberTracks() {
        document.querySelectorAll('.campaign-track-row').forEach((row, i) => {
            row.querySelector('span').textContent = i + 1;
        });
        trackCount = document.querySelectorAll('.campaign-track-row').length;
    }

    // ── Campaign: Create ─────────────────────────────────────────────────
    document.getElementById('createCampaignBtn')?.addEventListener('click', function() {
        const name = document.getElementById('campaign_name').value.trim();
        const platform = document.querySelector('input[name="campaign_platform"]:checked')?.value || 'spotify';
        const channelUrl = document.getElementById('campaign_channel_url')?.value.trim() || null;

        if (!name) { showNotification('Please enter a campaign name', 'error'); return; }

        const interstitialEnabled = document.getElementById('campaign_interstitial_enabled')?.checked || false;
        let interstitialEvery = null;
        let interstitialMediaUrl = null;
        let interstitialDuration = 120;
        if (interstitialEnabled) {
            interstitialEvery = parseInt(document.getElementById('campaign_interstitial_every')?.value) || 5;
            interstitialMediaUrl = document.getElementById('campaign_interstitial_media_url')?.value.trim() || null;
            interstitialDuration = parseInt(document.getElementById('campaign_interstitial_duration')?.value) || 120;
            if (!interstitialMediaUrl) {
                showNotification('Enter an interstitial media URL or disable interstitial', 'error');
                return;
            }
        }

        const tracks = [];
        document.querySelectorAll('.campaign-track-row').forEach(row => {
            const url = row.querySelector('.campaign-track-url').value.trim();
            const title = row.querySelector('.campaign-track-title').value.trim();
            const duration = parseInt(row.querySelector('.campaign-track-duration').value) || 180;
            const trackType = row.querySelector('.campaign-track-type')?.value || 'playlist';
            if (url) {
                tracks.push({ media_url: url, media_title: title || null, duration_seconds: duration, track_type: trackType });
            }
        });

        if (tracks.length === 0) { showNotification('Add at least one track', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating...';

        fetch('/api/campaigns', {
            method: 'POST', headers: headers,
            body: JSON.stringify({ name, platform, tracks, channel_url: channelUrl, interstitial_every: interstitialEvery, interstitial_media_url: interstitialMediaUrl, interstitial_duration_seconds: interstitialDuration })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message || 'Campaign created!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Failed: ' + (data.message || 'Error'), 'error');
                this.disabled = false; this.innerHTML = '<i class="fas fa-rocket mr-3"></i> Create Campaign';
            }
        })
        .catch(err => {
            showNotification('Error: ' + err, 'error');
            this.disabled = false; this.innerHTML = '<i class="fas fa-rocket mr-3"></i> Create Campaign';
        });
    });

    // ── Campaign: Deploy (open modal) ──────────────────────────────────────
    document.querySelectorAll('.deploy-campaign').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('deploy_campaign_id').value = this.dataset.campaignId;
            document.getElementById('deployCampaignName').textContent = this.dataset.campaignName;
            // Uncheck all devices
            document.querySelectorAll('.deploy-device-cb').forEach(cb => cb.checked = false);
            updateDeployCount();
            document.getElementById('deployCampaignModal').classList.remove('hidden');
        });
    });

    // Deploy modal: select all / clear
    document.getElementById('deploySelectAll')?.addEventListener('click', () => {
        document.querySelectorAll('.deploy-device-cb:not(:disabled)').forEach(cb => cb.checked = true);
        updateDeployCount();
    });
    document.getElementById('deployClearAll')?.addEventListener('click', () => {
        document.querySelectorAll('.deploy-device-cb').forEach(cb => cb.checked = false);
        updateDeployCount();
    });
    document.querySelectorAll('.deploy-device-cb').forEach(cb => cb.addEventListener('change', updateDeployCount));

    function updateDeployCount() {
        const count = document.querySelectorAll('.deploy-device-cb:checked').length;
        document.getElementById('deploySelectedCount').textContent = count;
    }

    // Deploy modal: confirm
    document.getElementById('confirmDeployCampaign')?.addEventListener('click', function() {
        const campaignId = document.getElementById('deploy_campaign_id').value;
        const deviceIds = [];
        document.querySelectorAll('.deploy-device-cb:checked').forEach(cb => deviceIds.push(parseInt(cb.value)));

        if (deviceIds.length === 0) { showNotification('Select at least one device', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Deploying...';

        fetch(`/api/campaigns/${campaignId}/deploy`, {
            method: 'POST', headers: headers,
            body: JSON.stringify({ device_ids: deviceIds })
        })
        .then(r => r.json())
        .then(data => {
            showNotification(data.message || 'Deployed!', data.success ? 'success' : 'error');
            document.getElementById('deployCampaignModal').classList.add('hidden');
            this.disabled = false; this.innerHTML = '<i class="fas fa-rocket mr-2"></i>Deploy Now';
            if (data.success) {
                window.open('{{ route('assignments.index') }}', '_blank');
                setTimeout(() => location.reload(), 1000);
            }
        })
        .catch(err => {
            showNotification('Error: ' + err, 'error');
            this.disabled = false; this.innerHTML = '<i class="fas fa-rocket mr-2"></i>Deploy Now';
        });
    });

    // ── Campaign: Delete ─────────────────────────────────────────────────
    document.querySelectorAll('.delete-campaign').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!confirm('Delete this campaign? Active deployments will be stopped.')) return;
            fetch(`/api/campaigns/${this.dataset.campaignId}`, { method: 'DELETE', headers: headers })
            .then(r => r.json())
            .then(data => { showNotification(data.message || 'Deleted', 'success'); setTimeout(() => location.reload(), 800); })
            .catch(err => showNotification('Error: ' + err, 'error'));
        });
    });

    // ── Campaign: Edit (Open Modal & Populate) ──────────────────────────
    document.querySelectorAll('.edit-campaign').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.campaignId;
            const name = this.dataset.campaignName;
            const platform = this.dataset.campaignPlatform;
            const tracks = JSON.parse(this.dataset.tracks || '[]');
            const channelUrl = this.dataset.channelUrl || '';
            const interstitialEvery = this.dataset.interstitialEvery ? parseInt(this.dataset.interstitialEvery) : null;
            const interstitialMediaUrl = this.dataset.interstitialMediaUrl || '';
            const interstitialDuration = this.dataset.interstitialDuration ? parseInt(this.dataset.interstitialDuration) : 120;

            document.getElementById('edit_campaign_id').value = id;
            document.getElementById('edit_campaign_name').value = name;
            document.getElementById('edit_campaign_channel_url').value = channelUrl;
            
            // Set platform
            document.querySelectorAll('.edit-campaign-platform-btn').forEach(b => {
                const isSelected = b.dataset.platform === platform;
                b.classList.toggle('border-amber-500', isSelected);
                b.classList.toggle('bg-amber-50', isSelected);
                b.classList.toggle('border-gray-200', !isSelected);
                b.querySelector('input').checked = isSelected;
            });
            document.getElementById('edit-campaign-channel-url-group').classList.toggle('hidden', platform !== 'youtube');

            // Set interstitial fields
            const interstitialCheckbox = document.getElementById('edit_campaign_interstitial_enabled');
            if (interstitialEvery && interstitialMediaUrl) {
                interstitialCheckbox.checked = true;
                document.getElementById('edit-campaign-interstitial-fields').classList.remove('hidden');
                document.getElementById('edit_campaign_interstitial_every').value = interstitialEvery;
                document.getElementById('edit_campaign_interstitial_media_url').value = interstitialMediaUrl;
                document.getElementById('edit_campaign_interstitial_duration').value = interstitialDuration;
            } else {
                interstitialCheckbox.checked = false;
                document.getElementById('edit-campaign-interstitial-fields').classList.add('hidden');
                document.getElementById('edit_campaign_interstitial_every').value = 5;
                document.getElementById('edit_campaign_interstitial_media_url').value = '';
                document.getElementById('edit_campaign_interstitial_duration').value = 120;
            }

            // Populate tracks
            const container = document.getElementById('editCampaignTracksContainer');
            container.innerHTML = '';
            tracks.forEach((track, i) => {
                addEditTrackRow(track.media_url, track.media_title, track.duration_seconds, track.track_type || 'playlist', i + 1);
            });

            document.getElementById('editCampaignModal').classList.remove('hidden');
        });
    });

    // Edit Platform Toggle
    document.querySelectorAll('.edit-campaign-platform-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.edit-campaign-platform-btn').forEach(b => {
                b.classList.remove('border-amber-500','bg-amber-50');
                b.classList.add('border-gray-200');
            });
            this.classList.add('border-amber-500','bg-amber-50');
            this.classList.remove('border-gray-200');
            this.querySelector('input').checked = true;
            const isYoutube = this.dataset.platform === 'youtube';
            document.getElementById('edit-campaign-channel-url-group').classList.toggle('hidden', !isYoutube);
        });
    });

    // Edit interstitial toggle
    document.getElementById('edit_campaign_interstitial_enabled')?.addEventListener('change', function() {
        document.getElementById('edit-campaign-interstitial-fields').classList.toggle('hidden', !this.checked);
    });

    function addEditTrackRow(url = '', title = '', duration = 180, trackType = 'playlist', index = null) {
        const container = document.getElementById('editCampaignTracksContainer');
        const count = index || (container.querySelectorAll('.edit-track-row').length + 1);
        const row = document.createElement('div');
        row.className = 'edit-track-row flex items-center space-x-2';
        row.innerHTML = `
            <span class="text-xs text-gray-400 font-bold w-5 flex-shrink-0">${count}</span>
            <input type="text" placeholder="URL" value="${url}" class="edit-track-url flex-1 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
            <input type="text" placeholder="Title" value="${title || ''}" class="edit-track-title w-24 p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
            <input type="number" value="${duration}" min="30" max="7200" class="edit-track-duration w-16 p-2.5 border border-gray-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-amber-500">
            <select class="edit-track-type w-20 p-2.5 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-amber-500 bg-white">
                <option value="playlist" ${trackType === 'playlist' ? 'selected' : ''}>Playlist</option>
                <option value="channel_video" ${trackType === 'channel_video' ? 'selected' : ''}>Channel</option>
                <option value="similar_video" ${trackType === 'similar_video' ? 'selected' : ''}>Similar</option>
            </select>
            <button type="button" class="remove-edit-track-btn text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
        `;
        container.appendChild(row);
        row.querySelector('.remove-edit-track-btn').addEventListener('click', () => { 
            row.remove(); 
            document.querySelectorAll('.edit-track-row').forEach((r, idx) => r.querySelector('span').textContent = idx + 1);
        });
    }

    document.getElementById('editAddTrackBtn')?.addEventListener('click', () => addEditTrackRow());

    // Save Changes
    document.getElementById('saveCampaignChangesBtn')?.addEventListener('click', function() {
        const id = document.getElementById('edit_campaign_id').value;
        const name = document.getElementById('edit_campaign_name').value.trim();
        const platform = document.querySelector('input[name="edit_campaign_platform"]:checked')?.value;
        const channelUrl = document.getElementById('edit_campaign_channel_url')?.value.trim() || null;

        if (!name) { showNotification('Name is required', 'error'); return; }

        const interstitialEnabled = document.getElementById('edit_campaign_interstitial_enabled')?.checked || false;
        let interstitialEvery = null;
        let interstitialMediaUrl = null;
        let interstitialDuration = 120;
        if (interstitialEnabled) {
            interstitialEvery = parseInt(document.getElementById('edit_campaign_interstitial_every')?.value) || 5;
            interstitialMediaUrl = document.getElementById('edit_campaign_interstitial_media_url')?.value.trim() || null;
            interstitialDuration = parseInt(document.getElementById('edit_campaign_interstitial_duration')?.value) || 120;
            if (!interstitialMediaUrl) {
                showNotification('Enter an interstitial media URL or disable interstitial', 'error');
                return;
            }
        }

        const tracks = [];
        document.querySelectorAll('.edit-track-row').forEach(row => {
            const url = row.querySelector('.edit-track-url').value.trim();
            const title = row.querySelector('.edit-track-title').value.trim();
            const duration = parseInt(row.querySelector('.edit-track-duration').value) || 180;
            const trackType = row.querySelector('.edit-track-type')?.value || 'playlist';
            if (url) tracks.push({ media_url: url, media_title: title || null, duration_seconds: duration, track_type: trackType });
        });

        if (tracks.length === 0) { showNotification('Add at least one track', 'error'); return; }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

        fetch(`/api/campaigns/${id}`, {
            method: 'PUT', headers: headers,
            body: JSON.stringify({ name, platform, tracks, channel_url: channelUrl, interstitial_every: interstitialEvery, interstitial_media_url: interstitialMediaUrl, interstitial_duration_seconds: interstitialDuration })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('Campaign updated!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + (data.message || 'Unknown'), 'error');
                this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Save Changes';
            }
        })
        .catch(err => {
            showNotification('Error: ' + err, 'error');
            this.disabled = false; this.innerHTML = '<i class="fas fa-save mr-2"></i>Save Changes';
        });
    });

    // ── Log Level Filter ─────────────────────────────────────────────────
    document.querySelectorAll('.log-filter').forEach(btn => {
        btn.addEventListener('click', function() {
            const lvl = this.dataset.level;
            document.querySelectorAll('.log-filter').forEach(b => b.style.opacity = '0.5');
            this.style.opacity = '1';
            document.querySelectorAll('.log-row').forEach(row => {
                const rl = row.dataset.level;
                row.style.display = (lvl === 'all') ? '' : (lvl === 'error' ? ((rl === 'error' || rl === 'critical') ? '' : 'none') : (rl === lvl ? '' : 'none'));
                const next = row.nextElementSibling;
                if (next && !next.classList.contains('log-row')) { next.style.display = row.style.display; if (row.style.display === 'none') next.classList.add('hidden'); }
            });
        });
    });

    // ── Toast Notification ───────────────────────────────────────────────
    function showNotification(message, type) {
        const existing = document.querySelector('.notification-toast');
        if (existing) existing.remove();
        const colors = { success: 'bg-gradient-to-r from-green-500 to-emerald-500', error: 'bg-gradient-to-r from-red-500 to-rose-500', info: 'bg-gradient-to-r from-blue-500 to-cyan-500' };
        const icons = { success: 'fa-check-circle', error: 'fa-exclamation-triangle', info: 'fa-info-circle' };
        const toast = document.createElement('div');
        toast.className = `notification-toast fixed top-6 right-6 ${colors[type]} text-white px-6 py-4 rounded-xl shadow-2xl z-[200] flex items-center smooth-transition slide-in`;
        toast.innerHTML = `<i class="fas ${icons[type]} mr-3 text-xl"></i><div><p class="font-semibold">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info'}</p><p class="text-sm opacity-90">${message}</p></div>`;
        document.body.appendChild(toast);
        setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); }, 5000);
        toast.addEventListener('click', () => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 300); });
    }
    // ── Live Updates & Audio Notifications ──────────────────────────────
    let lastStatsState = '';
    const deviceStatuses = {}; // Local cache to track changes
    let firstLoadDone = false;
    let pollTimer = null;

    // Sounds
    const sounds = {
        online: new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'),
        offline: new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3')
    };

    function updateLiveDashboard() {
        if (pollTimer) clearTimeout(pollTimer);
        
        // Cache bust with timestamp, ensure we don't use disk cache
        fetch(`/api/dashboard/stats?t=${Date.now()}`, { 
            headers: headers,
            cache: 'no-store'
        })
        .then(r => {
            if (!r.ok) throw new Error(`HTTP Error: ${r.status}`);
            return r.json();
        })
        .then(data => {
            if (!data.success) return;

            // 1. Process Device Updates
            data.devices.forEach(device => {
                const oldStatus = deviceStatuses[device.id];
                const newStatus = device.status;

                // Handle status change sounds/notifications
                if (firstLoadDone && oldStatus !== undefined && oldStatus !== newStatus) {
                    if ((oldStatus === 'offline') && (newStatus === 'online' || newStatus === 'streaming')) {
                        sounds.online.play().catch(() => {});
                        showNotification(`${device.name || 'Device'} is now ONLINE`, 'success');
                    } else if (newStatus === 'offline' && oldStatus !== 'offline') {
                        sounds.offline.play().catch(() => {});
                        showNotification(`${device.name || 'Device'} went OFFLINE`, 'info');
                    }
                }
                deviceStatuses[device.id] = newStatus;

                // Update individual device cards
                const card = document.getElementById(`device-card-${device.id}`);
                const assignLabel = document.getElementById(`assign-device-${device.id}`);
                
                if (card) {
                    const config = {
                        online: { b:'bg-green-100', c:'text-green-600', i:'fa-wifi', d:'bg-green-500' },
                        streaming: { b:'bg-blue-100', c:'text-blue-600', i:'fa-music', d:'bg-blue-500' },
                        offline: { b:'bg-gray-100', c:'text-gray-400', i:'fa-power-off', d:'bg-gray-400' }
                    }[newStatus] || { b:'bg-gray-100', c:'text-gray-400', i:'fa-power-off', d:'bg-gray-400' };

                    // Update Icon Wrapper
                    const icoWrap = card.querySelector('.h-8.w-8.rounded-full');
                    if (icoWrap && !icoWrap.className.includes(config.b)) {
                        icoWrap.className = `h-8 w-8 rounded-full ${config.b} flex items-center justify-center`;
                    }
                    
                    // Update Icon
                    const ico = card.querySelector('.h-8.w-8 i');
                    if (ico && !ico.className.includes(config.i)) {
                        ico.className = `fas ${config.i} ${config.c} text-xs`;
                    }

                    // Update Dot
                    const dot = card.querySelector('.absolute.-bottom-0.5.-right-0.5');
                    if (dot) {
                        dot.className = `absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full ${config.d} border-2 border-white ${newStatus !== 'offline' ? 'pulse-dot' : ''}`;
                    }

                    // Update Status Text
                    const timeP = card.querySelector('p.text-xs.text-gray-400');
                    if (timeP && newStatus !== 'offline') {
                        timeP.textContent = 'Active now';
                    }
                }

                if (assignLabel) {
                    const cb = assignLabel.querySelector('input');
                    const isOffline = newStatus === 'offline';
                    if (cb.disabled !== isOffline) {
                        cb.disabled = isOffline;
                        assignLabel.classList.toggle('opacity-50', isOffline);
                    }
                }
            });

            // 2. Global State Changes (counts and tasks)
            const state = JSON.stringify({
                counts: data.counts,
                tasks: (data.activeAssignments || []).map(a => ({id:a.id, s:a.status, d:a.device_id}))
            });

            if (state !== lastStatsState) {
                // Check if we need a full refresh (new devices added or task list changed significantly)
                const currentCards = document.querySelectorAll('[id^="device-card-"]').length;
                const currentTasks = document.querySelectorAll('[data-assignment-id]').length;
                
                if (firstLoadDone) {
                    if (data.devices.length > currentCards) {
                        showNotification('New device detected! Refresh for full list.', 'info');
                    } else if (data.activeAssignments && data.activeAssignments.length !== currentTasks) {
                        // For tasks, we might want to be more subtle or actually refresh the task list
                        // But since it's a "Farm", tasks change often.
                    }
                }

                lastStatsState = state;

                // Update Counter Cards
                updateText('stat-online', data.counts.online);
                updateText('stat-streaming', data.counts.streaming);
                updateText('stat-offline', data.counts.offline);
                updateText('stat-total', data.counts.total);
                updateText('stat-active', data.counts.activeTasks);
                updateText('nav-total-count', data.counts.total);
                updateText('nav-active-count', data.counts.activeTasks);
                updateText('device-count-badge', data.counts.total);

                // 3. Live-refresh Active Tasks panel
                const tasksContainer = document.getElementById('active-tasks-container');
                if (tasksContainer && data.activeAssignments) {
                    const statusColors = {
                        'pending': 'bg-amber-100 text-amber-700',
                        'playing': 'bg-green-100 text-green-700',
                        'paused':  'bg-blue-100 text-blue-700',
                        'stopped': 'bg-gray-100 text-gray-700',
                        'failed':  'bg-red-100 text-red-700',
                    };

                    if (data.activeAssignments.length > 0) {
                        let html = '<div class="space-y-3 max-h-[400px] overflow-y-auto pr-1">';
                        data.activeAssignments.forEach(a => {
                            const platform = a.platform || 'youtube';
                            const pIcon = platform === 'spotify' ? 'text-green-500' : (platform === 'apple_music' ? 'text-red-600' : 'text-red-500');
                            const brandIcon = platform === 'apple_music' ? 'apple' : platform;
                            const sColor = statusColors[a.status] || 'bg-gray-100 text-gray-700';
                            const title = a.media_title || 'Untitled';
                            const deviceName = (a.device && a.device.name) ? a.device.name : 'Unknown Device';
                            const mediaUrl = a.media_url || '';
                            const shortUrl = mediaUrl.length > 40 ? mediaUrl.substring(0, 40) + '...' : mediaUrl;

                            html += `<div class="p-3 border border-gray-200 rounded-xl smooth-transition hover:shadow-sm" data-assignment-id="${a.id}">`;
                            html += `<div class="flex items-start justify-between mb-2">`;
                            html += `<div class="flex items-center min-w-0">`;
                            html += `<i class="fab fa-${brandIcon} ${pIcon} text-lg mr-2 flex-shrink-0"></i>`;
                            html += `<div class="min-w-0">`;
                            html += `<p class="font-medium text-sm text-gray-900 truncate">${title}</p>`;
                            html += `<p class="text-xs text-gray-400 truncate">${deviceName}</p>`;
                            html += `</div></div>`;
                            html += `<span class="text-xs px-2 py-0.5 rounded-full font-medium ${sColor} flex-shrink-0">${a.status.charAt(0).toUpperCase() + a.status.slice(1)}</span>`;
                            html += `</div>`;
                            html += `<p class="text-xs text-gray-400 font-mono truncate mb-2">${shortUrl}</p>`;
                            html += `<div class="flex space-x-2">`;

                            if (a.status === 'paused' || a.status === 'pending') {
                                html += `<button class="assignment-control flex-1 text-xs px-2 py-1.5 bg-green-50 text-green-600 rounded-lg hover:bg-green-100 smooth-transition font-medium" data-id="${a.id}" data-action="play"><i class="fas fa-play mr-1"></i>Play</button>`;
                            }
                            if (a.status === 'playing') {
                                html += `<button class="assignment-control flex-1 text-xs px-2 py-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 smooth-transition font-medium" data-id="${a.id}" data-action="pause"><i class="fas fa-pause mr-1"></i>Pause</button>`;
                            }
                            html += `<button class="assignment-control flex-1 text-xs px-2 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 smooth-transition font-medium" data-id="${a.id}" data-action="stop"><i class="fas fa-stop mr-1"></i>Stop</button>`;
                            html += `</div></div>`;
                        });
                        html += '</div>';
                        tasksContainer.innerHTML = html;

                        // Re-bind event listeners for dynamically created buttons
                        tasksContainer.querySelectorAll('.assignment-control').forEach(btn => {
                            btn.addEventListener('click', function(e) {
                                e.stopPropagation();
                                const id = this.dataset.id;
                                const action = this.dataset.action;
                                const endpoint = action === 'stop' ? `/api/assignments/${id}` : `/api/assignments/${id}/control`;
                                const method = action === 'stop' ? 'DELETE' : 'POST';
                                const body = action === 'stop' ? undefined : JSON.stringify({ action: action });
                                this.disabled = true;
                                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                                fetch(endpoint, { method, headers, body })
                                    .then(r => r.json())
                                    .then(d => { showNotification(d.message || 'Done', d.success ? 'success' : 'error'); })
                                    .catch(err => { showNotification('Error: ' + err, 'error'); });
                            });
                        });
                    } else {
                        tasksContainer.innerHTML = `
                            <div class="text-center py-8">
                                <div class="h-14 w-14 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                    <i class="fas fa-tasks text-gray-400 text-xl"></i>
                                </div>
                                <p class="text-sm text-gray-500">No active tasks</p>
                                <p class="text-xs text-gray-400">Assign a track to a device to get started</p>
                            </div>`;
                    }
                }
            }

            firstLoadDone = true;
        })
        .catch(err => {
            console.warn('Dash poll failed:', err);
        })
        .finally(() => {
            // Schedule next poll - 3 seconds for better responsiveness
            pollTimer = setTimeout(updateLiveDashboard, 3000);
        });
    }

    function updateText(id, val) {
        const el = document.getElementById(id);
        if (el && el.textContent != val) el.textContent = val;
    }

    // Initialize Dashboard Update immediately
    updateLiveDashboard();
});
</script>
