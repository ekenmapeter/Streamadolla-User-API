<!-- Manual Register Modal -->
<div id="manualRegisterModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-slate-800 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-plus-circle mr-2 text-primary-400"></i>Manual Device Registration</h3>
            <button onclick="document.getElementById('manualRegisterModal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Display Name</label>
                    <input type="text" id="manual_name" placeholder="e.g. Pixel 6 Pro" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Device ID</label>
                    <input type="text" id="manual_device_id" placeholder="Copy from Phone app Status tab" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">FCM Token</label>
                    <textarea id="manual_fcm_token" rows="4" placeholder="Paste the long token here..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-all text-xs font-mono"></textarea>
                </div>
            </div>
            <div class="mt-8 flex space-x-3">
                <button onclick="document.getElementById('manualRegisterModal').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancel</button>
                <button id="submitManualRegister" class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-200 transition-all flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i>Register Device
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Play on Single Device Modal -->
<div id="playDeviceModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="bg-gradient-to-r from-primary-600 to-blue-500 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-play-circle mr-2"></i>Assign to Device</h3>
            <button onclick="document.getElementById('playDeviceModal').classList.add('hidden')" class="text-white/80 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="modal_device_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-primary-500 bg-primary-50 text-xs" data-platform="spotify">
                            <input type="radio" name="modal_platform" value="spotify" class="hidden" checked>
                            <i class="fab fa-spotify text-green-500 mr-1 sm:mr-2"></i><span class="font-medium">Spotify</span>
                        </label>
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200 text-xs" data-platform="apple_music">
                            <input type="radio" name="modal_platform" value="apple_music" class="hidden">
                            <i class="fab fa-apple text-red-600 mr-1 sm:mr-2"></i><span class="font-medium">Apple Music</span>
                        </label>
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200 text-xs" data-platform="youtube">
                            <input type="radio" name="modal_platform" value="youtube" class="hidden">
                            <i class="fab fa-youtube text-red-500 mr-1 sm:mr-2"></i><span class="font-medium">YouTube</span>
                        </label>
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200 text-xs" data-platform="tidal">
                            <input type="radio" name="modal_platform" value="tidal" class="hidden">
                            <i class="fas fa-water text-cyan-500 mr-1 sm:mr-2"></i><span class="font-medium">TIDAL</span>
                        </label>
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200 text-xs" data-platform="iheart">
                            <input type="radio" name="modal_platform" value="iheart" class="hidden">
                            <i class="fas fa-heart text-pink-500 mr-1 sm:mr-2"></i><span class="font-medium">iHeartRadio</span>
                        </label>
                        <label class="modal-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200 text-xs" data-platform="audiomack">
                            <input type="radio" name="modal_platform" value="audiomack" class="hidden">
                            <i class="fas fa-headphones text-yellow-600 mr-1 sm:mr-2"></i><span class="font-medium">Audiomack</span>
                        </label>
                    </div>
                </div>
                <div id="modal-spotify-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Spotify URI</label>
                    <input type="text" id="modal_spotify_uri" value="spotify:track:4cOdK2wGLETKBW3PvgPWqT" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
                <div id="modal-apple_music-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Apple Music URL</label>
                    <input type="text" id="modal_apple_music_url" placeholder="https://music.apple.com/..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                </div>
                <div id="modal-youtube-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">YouTube URL</label>
                    <input type="text" id="modal_youtube_url" placeholder="https://www.youtube.com/watch?v=..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 outline-none">
                </div>
                <div id="modal-tidal-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">TIDAL URL</label>
                    <input type="text" id="modal_tidal_url" placeholder="https://tidal.com/browse/track/..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-cyan-500 outline-none">
                </div>
                <div id="modal-iheart-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">iHeartRadio URL</label>
                    <input type="text" id="modal_iheart_url" placeholder="https://www.iheart.com/..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 outline-none">
                </div>
                <div id="modal-audiomack-group" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Audiomack URL</label>
                    <input type="text" id="modal_audiomack_url" placeholder="https://audiomack.com/..." class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-600 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Task Label <span class="text-gray-400">(optional)</span></label>
                    <input type="text" id="modal_media_title" placeholder="e.g. Morning Vibes" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-primary-500 outline-none">
                </div>
            </div>
            <div class="mt-8 flex space-x-3">
                <button onclick="document.getElementById('playDeviceModal').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancel</button>
                <button id="confirmPlayDevice" class="flex-1 px-4 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold shadow-lg shadow-primary-200 transition-all flex items-center justify-center">
                    <i class="fas fa-paper-plane mr-2"></i>Assign & Play
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Deploy Campaign Modal -->
<div id="deployCampaignModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-600 to-green-500 px-6 py-4 flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-white"><i class="fas fa-rocket mr-2"></i>Deploy Campaign</h3>
                <p class="text-emerald-100 text-sm" id="deployCampaignName"></p>
            </div>
            <button onclick="document.getElementById('deployCampaignModal').classList.add('hidden')" class="text-white/80 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="deploy_campaign_id">

            <div class="mb-4">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700"><i class="fas fa-mobile-alt mr-2 text-emerald-500"></i>Select Devices</label>
                    <div class="flex space-x-2">
                        <button type="button" id="deploySelectAll" class="text-xs px-3 py-1 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 smooth-transition font-medium">Select All</button>
                        <button type="button" id="deployClearAll" class="text-xs px-3 py-1 bg-gray-50 text-gray-600 rounded-lg hover:bg-gray-100 smooth-transition font-medium">Clear</button>
                    </div>
                </div>
                <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1" id="deployDeviceList">
                    @foreach($devices as $device)
                    @php
                        $dsc = ['online'=>['c'=>'text-green-600','b'=>'bg-green-100','i'=>'fa-wifi'],'streaming'=>['c'=>'text-blue-600','b'=>'bg-blue-100','i'=>'fa-music'],'offline'=>['c'=>'text-gray-400','b'=>'bg-gray-100','i'=>'fa-power-off']];
                        $dcfg = $dsc[$device->status] ?? $dsc['offline'];
                    @endphp
                    <label class="flex items-center p-3 border-2 border-gray-200 rounded-xl cursor-pointer smooth-transition hover:border-emerald-300 {{ $device->status === 'offline' ? 'opacity-50' : '' }}">
                        <input type="checkbox" class="deploy-device-cb h-4 w-4 text-emerald-600 rounded border-gray-300 mr-3" value="{{ $device->id }}" {{ $device->status === 'offline' ? 'disabled' : '' }}>
                        <div class="h-7 w-7 rounded-full {{ $dcfg['b'] }} flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas {{ $dcfg['i'] }} {{ $dcfg['c'] }} text-xs"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-sm text-gray-900 truncate">{{ $device->name ?? 'Unnamed' }}</p>
                            <p class="text-xs text-gray-400">{{ ucfirst($device->status) }}</p>
                        </div>
                    </label>
                    @endforeach
                </div>
                <p class="text-xs text-gray-400 mt-2"><span id="deploySelectedCount">0</span> device(s) selected</p>
            </div>

            <div class="flex space-x-3">
                <button onclick="document.getElementById('deployCampaignModal').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancel</button>
                <button id="confirmDeployCampaign" class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-600 to-green-500 hover:from-emerald-700 hover:to-green-600 text-white rounded-xl font-semibold shadow-lg transition-all flex items-center justify-center">
                    <i class="fas fa-rocket mr-2"></i>Deploy Now
                </button>
            </div>
        </div>
    </div>
</div>
<!-- Edit Campaign Modal -->
<div id="editCampaignModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-[100] items-center justify-center hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-amber-600 to-amber-500 px-6 py-4 flex items-center justify-between">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-edit mr-2"></i>Edit Campaign</h3>
            <button onclick="document.getElementById('editCampaignModal').classList.add('hidden')" class="text-white/80 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
        <div class="p-6">
            <input type="hidden" id="edit_campaign_id">
            
            <!-- Campaign Name -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Campaign Name</label>
                <input type="text" id="edit_campaign_name" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
            </div>

            <!-- Platform -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Platform</label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="edit-campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="spotify">
                        <input type="radio" name="edit_campaign_platform" value="spotify" class="hidden">
                        <i class="fab fa-spotify text-green-500 text-xl mr-2"></i><span class="font-medium">Spotify</span>
                    </label>
                    <label class="edit-campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="apple_music">
                        <input type="radio" name="edit_campaign_platform" value="apple_music" class="hidden">
                        <i class="fab fa-apple text-red-600 text-xl mr-2"></i><span class="font-medium">Apple Music</span>
                    </label>
                    <label class="edit-campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="youtube">
                        <input type="radio" name="edit_campaign_platform" value="youtube" class="hidden">
                        <i class="fab fa-youtube text-red-500 text-xl mr-2"></i><span class="font-medium">YouTube</span>
                    </label>
                    <label class="edit-campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="tidal">
                        <input type="radio" name="edit_campaign_platform" value="tidal" class="hidden">
                        <i class="fas fa-water text-cyan-500 text-xl mr-2"></i><span class="font-medium">TIDAL</span>
                    </label>
                    <label class="edit-campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="iheart">
                        <input type="radio" name="edit_campaign_platform" value="iheart" class="hidden">
                        <i class="fas fa-heart text-pink-500 text-xl mr-2"></i><span class="font-medium">iHeartRadio</span>
                    </label>
                    <label class="edit-campaign-platform-btn flex items-center p-3 border-2 rounded-xl cursor-pointer smooth-transition border-gray-200" data-platform="audiomack">
                        <input type="radio" name="edit_campaign_platform" value="audiomack" class="hidden">
                        <i class="fas fa-headphones text-yellow-600 text-xl mr-2"></i><span class="font-medium">Audiomack</span>
                    </label>
                </div>
            </div>

            <!-- Edit: Channel URL -->
            <div id="edit-campaign-channel-url-group" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-2"><i class="fab fa-youtube mr-2 text-red-500"></i>YouTube Channel URL</label>
                <input type="text" id="edit_campaign_channel_url" placeholder="https://www.youtube.com/@ChannelName" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-amber-500">
            </div>

            <!-- Edit: Interstitial Settings -->
            <div class="mb-4 p-4 bg-amber-50 rounded-xl border border-amber-200">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-medium text-amber-800"><i class="fas fa-random mr-2 text-amber-600"></i>Interstitial Content</label>
                    <label class="flex items-center text-xs text-amber-700 cursor-pointer">
                        <input type="checkbox" id="edit_campaign_interstitial_enabled" class="h-4 w-4 text-amber-600 rounded border-amber-300 mr-1">
                        <span>Enabled</span>
                    </label>
                </div>
                <div id="edit-campaign-interstitial-fields" class="space-y-3 hidden">
                    <div>
                        <label class="block text-xs font-medium text-amber-700 mb-1">Play interstitial after every</label>
                        <div class="flex items-center space-x-2">
                            <input type="number" id="edit_campaign_interstitial_every" value="5" min="1" max="100" class="w-20 p-2.5 border border-amber-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-amber-500">
                            <span class="text-xs text-amber-600">playlist tracks</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-amber-700 mb-1">Interstitial Media URL</label>
                        <input type="text" id="edit_campaign_interstitial_media_url" placeholder="spotify:track:xxx or YouTube URL" class="w-full p-2.5 border border-amber-300 rounded-lg text-sm focus:ring-2 focus:ring-amber-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-amber-700 mb-1">Interstitial Duration (seconds)</label>
                        <input type="number" id="edit_campaign_interstitial_duration" value="120" min="30" max="7200" class="w-20 p-2.5 border border-amber-300 rounded-lg text-sm text-center focus:ring-2 focus:ring-amber-500">
                    </div>
                </div>
            </div>

            <!-- Tracks -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Tracks</label>
                    <button type="button" id="editAddTrackBtn" class="text-xs px-3 py-1 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 smooth-transition font-medium"><i class="fas fa-plus mr-1"></i>Add Track</button>
                </div>
                <div id="editCampaignTracksContainer" class="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                    <!-- Tracks will be populated here -->
                </div>
            </div>

            <div class="mt-8 flex space-x-3">
                <button onclick="document.getElementById('editCampaignModal').classList.add('hidden')" class="flex-1 px-4 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors">Cancel</button>
                <button id="saveCampaignChangesBtn" class="flex-1 px-4 py-3 bg-gradient-to-r from-amber-600 to-amber-500 hover:from-amber-700 hover:to-amber-600 text-white rounded-xl font-semibold shadow-lg transition-all flex items-center justify-center">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
