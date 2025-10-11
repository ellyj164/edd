<?php
/**
 * Live Streaming Interface
 * Full WebRTC-based streaming setup and broadcast interface for sellers
 */

require_once __DIR__ . '/../includes/init.php';

// Require vendor login
Session::requireLogin();

// Load models
$vendor = new Vendor();
$product = new Product();

// Check if user is a vendor
$vendorInfo = $vendor->findByUserId(Session::getUserId());
if (!$vendorInfo || $vendorInfo['status'] !== 'approved') {
    redirect('/seller-onboarding.php');
}

$vendorId = $vendorInfo['id'];

// Get selected products from session or query parameter
$selectedProductIds = isset($_GET['products']) ? explode(',', $_GET['products']) : [];

$page_title = 'Live Stream Setup - Seller Dashboard';
$meta_description = 'Set up your camera and microphone for live streaming.';

include __DIR__ . '/../templates/seller-header.php';
?>

<style>
.stream-interface-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.stream-setup-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.stream-header {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: white;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stream-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    font-weight: 600;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #10b981;
}

.status-indicator.offline {
    background: #6b7280;
}

.status-indicator.live {
    background: #ef4444;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.stream-grid {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 20px;
    padding: 24px;
}

.video-preview-section {
    background: #000;
    border-radius: 8px;
    position: relative;
    aspect-ratio: 16/9;
    overflow: hidden;
}

#videoPreview {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-controls {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 12px;
    background: rgba(0,0,0,0.7);
    padding: 12px 20px;
    border-radius: 50px;
}

.control-btn {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: none;
    background: white;
    color: #333;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    transition: all 0.2s;
}

.control-btn:hover {
    transform: scale(1.1);
}

.control-btn.active {
    background: #dc2626;
    color: white;
}

.control-btn.go-live {
    background: #dc2626;
    color: white;
    width: auto;
    padding: 0 24px;
    font-size: 16px;
    font-weight: 600;
}

.control-btn.go-live:hover {
    background: #b91c1c;
}

.setup-panel {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.panel-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
}

.panel-section h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}

.device-select {
    width: 100%;
    padding: 10px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    margin-bottom: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.stat-item {
    background: white;
    padding: 12px;
    border-radius: 6px;
    text-align: center;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #dc2626;
}

.stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

.products-list {
    max-height: 200px;
    overflow-y: auto;
}

.product-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    background: white;
    border-radius: 6px;
    margin-bottom: 8px;
}

.product-thumbnail {
    width: 48px;
    height: 48px;
    background: #f3f4f6;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.product-info {
    flex: 1;
}

.product-name {
    font-size: 14px;
    font-weight: 600;
    color: #333;
}

.product-price {
    font-size: 12px;
    color: #dc2626;
    font-weight: 600;
}

.alert-box {
    background: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 6px;
    padding: 12px;
    margin-bottom: 16px;
}

.alert-box.info {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.alert-text {
    font-size: 14px;
    color: #991b1b;
}

.alert-box.info .alert-text {
    color: #1e40af;
}

@media (max-width: 1024px) {
    .stream-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="stream-interface-container">
    <div class="stream-setup-card">
        <div class="stream-header">
            <div>
                <h1 style="font-size: 24px; margin-bottom: 4px;">🔴 Live Stream Setup</h1>
                <p style="font-size: 14px; opacity: 0.9;">Configure your stream and go live</p>
            </div>
            <div class="stream-status">
                <span class="status-indicator offline" id="statusIndicator"></span>
                <span id="statusText">Setting Up</span>
            </div>
        </div>

        <div class="stream-grid">
            <div>
                <div class="alert-box info" id="permissionAlert" style="display: none;">
                    <div class="alert-text">
                        <strong>⚠️ Camera/Microphone Access Required</strong><br>
                        Please allow access to your camera and microphone to start streaming.
                    </div>
                </div>

                <div class="video-preview-section">
                    <video id="videoPreview" autoplay muted playsinline></video>
                    <div class="video-controls">
                        <button class="control-btn" id="toggleCamera" title="Toggle Camera">
                            <i class="fas fa-video"></i>
                        </button>
                        <button class="control-btn" id="toggleMic" title="Toggle Microphone">
                            <i class="fas fa-microphone"></i>
                        </button>
                        <button class="control-btn go-live" id="goLiveBtn" disabled>
                            Go Live
                        </button>
                    </div>
                </div>

                <div style="margin-top: 16px; padding: 16px; background: #f9fafb; border-radius: 8px;">
                    <h3 style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">Stream Title</h3>
                    <input type="text" id="streamTitle" placeholder="Enter your stream title..." 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                </div>
            </div>

            <div class="setup-panel">
                <div class="panel-section">
                    <h3>📹 Camera Settings</h3>
                    <select id="cameraSelect" class="device-select">
                        <option value="">Loading cameras...</option>
                    </select>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
                        <input type="checkbox" id="hdQuality"> 
                        <span>HD Quality (1080p)</span>
                    </label>
                </div>

                <div class="panel-section">
                    <h3>🎤 Microphone Settings</h3>
                    <select id="micSelect" class="device-select">
                        <option value="">Loading microphones...</option>
                    </select>
                    <div style="margin-top: 8px;">
                        <label style="font-size: 12px; color: #6b7280;">Audio Level</label>
                        <div style="height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden;">
                            <div id="audioLevel" style="height: 100%; width: 0%; background: #10b981; transition: width 0.1s;"></div>
                        </div>
                    </div>
                </div>

                <div class="panel-section">
                    <h3>📊 Stream Stats</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value" id="viewerCount">0</div>
                            <div class="stat-label">Current Viewers</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="duration">00:00</div>
                            <div class="stat-label">Duration</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="likesCount">0</div>
                            <div class="stat-label">👍 Likes</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="dislikesCount">0</div>
                            <div class="stat-label">👎 Dislikes</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="commentsCount">0</div>
                            <div class="stat-label">💬 Comments</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="ordersCount">0</div>
                            <div class="stat-label">🛒 Orders</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value" id="revenueAmount">$0.00</div>
                            <div class="stat-label">💰 Revenue</div>
                        </div>
                    </div>
                </div>

                <div class="panel-section">
                    <h3>👥 Active Viewers</h3>
                    <div id="viewersList" style="max-height: 150px; overflow-y: auto; font-size: 13px;">
                        <div style="text-align: center; color: #6b7280; padding: 10px;">
                            No viewers yet
                        </div>
                    </div>
                </div>

                <div class="panel-section">
                    <h3>💬 Live Comments</h3>
                    <div id="commentsFeed" style="max-height: 200px; overflow-y: auto; font-size: 13px;">
                        <div style="text-align: center; color: #6b7280; padding: 10px;">
                            No comments yet
                        </div>
                    </div>
                </div>

                <div class="panel-section">
                    <h3>🛍️ Stream Orders</h3>
                    <div id="ordersList" style="max-height: 150px; overflow-y: auto; font-size: 13px;">
                        <div style="text-align: center; color: #6b7280; padding: 10px;">
                            No orders yet
                        </div>
                    </div>
                </div>

                <div class="panel-section">
                    <h3>📦 Featured Products</h3>
                    <div class="products-list" id="productsList">
                        <div style="text-align: center; color: #6b7280; padding: 20px;">
                            No products selected
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- End Stream Modal -->
<div id="endStreamModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%;">
        <h2 style="margin-bottom: 20px; color: #1f2937;">End Your Live Stream</h2>
        
        <div id="streamSummary" style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <div>
                    <div style="font-size: 12px; color: #6b7280;">Duration</div>
                    <div style="font-size: 20px; font-weight: 600; color: #1f2937;" id="finalDuration">00:00</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6b7280;">Total Viewers</div>
                    <div style="font-size: 20px; font-weight: 600; color: #1f2937;" id="finalViewers">0</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6b7280;">Likes</div>
                    <div style="font-size: 20px; font-weight: 600; color: #1f2937;" id="finalLikes">0</div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6b7280;">Orders</div>
                    <div style="font-size: 20px; font-weight: 600; color: #1f2937;" id="finalOrders">0</div>
                </div>
            </div>
            <div>
                <div style="font-size: 12px; color: #6b7280;">Revenue</div>
                <div style="font-size: 24px; font-weight: 700; color: #10b981;" id="finalRevenue">$0.00</div>
            </div>
        </div>
        
        <p style="margin-bottom: 20px; color: #6b7280;">Would you like to save this stream for viewers to watch later?</p>
        
        <div style="display: flex; gap: 10px;">
            <button onclick="endStreamWithAction('save')" style="flex: 1; padding: 12px; background: #dc2626; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                💾 Save Stream
            </button>
            <button onclick="endStreamWithAction('delete')" style="flex: 1; padding: 12px; background: #6b7280; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                🗑️ Delete Stream
            </button>
        </div>
        
        <button onclick="cancelEndStream()" style="width: 100%; padding: 12px; background: white; color: #6b7280; border: 1px solid #d1d5db; border-radius: 6px; margin-top: 10px; cursor: pointer;">
            Cancel
        </button>
    </div>
</div>

<script>
// Global variables
let localStream = null;
let isStreaming = false;
let streamStartTime = null;
let durationInterval = null;
let statsInterval = null;
let audioContext = null;
let analyser = null;
let cameraEnabled = true;
let micEnabled = true;
let currentStreamId = null;  // Will be set when stream starts

// Initialize stream setup
async function initializeStream() {
    try {
        // Request camera and microphone permissions
        const constraints = {
            video: {
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            },
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true
            }
        };

        localStream = await navigator.mediaDevices.getUserMedia(constraints);
        
        // Display video preview
        const videoPreview = document.getElementById('videoPreview');
        videoPreview.srcObject = localStream;

        // Setup audio level monitoring
        setupAudioMonitoring();

        // Load available devices
        await loadDevices();

        // Enable go live button
        document.getElementById('goLiveBtn').disabled = false;
        updateStatus('ready', 'Ready to Go Live');

        // Hide permission alert
        document.getElementById('permissionAlert').style.display = 'none';

    } catch (error) {
        console.error('Error accessing media devices:', error);
        showPermissionAlert();
        updateStatus('error', 'Setup Failed');
    }
}

function showPermissionAlert() {
    document.getElementById('permissionAlert').style.display = 'block';
}

async function loadDevices() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        
        const cameraSelect = document.getElementById('cameraSelect');
        const micSelect = document.getElementById('micSelect');
        
        cameraSelect.innerHTML = '';
        micSelect.innerHTML = '';

        // Filter and populate camera select
        const cameras = devices.filter(device => device.kind === 'videoinput');
        cameras.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.textContent = device.label || `Camera ${index + 1}`;
            cameraSelect.appendChild(option);
        });

        // Filter and populate microphone select
        const microphones = devices.filter(device => device.kind === 'audioinput');
        microphones.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.textContent = device.label || `Microphone ${index + 1}`;
            micSelect.appendChild(option);
        });

    } catch (error) {
        console.error('Error loading devices:', error);
    }
}

function setupAudioMonitoring() {
    try {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
        analyser = audioContext.createAnalyser();
        const source = audioContext.createMediaStreamSource(localStream);
        source.connect(analyser);
        
        analyser.fftSize = 256;
        const bufferLength = analyser.frequencyBinCount;
        const dataArray = new Uint8Array(bufferLength);

        function updateAudioLevel() {
            analyser.getByteFrequencyData(dataArray);
            const average = dataArray.reduce((a, b) => a + b) / bufferLength;
            const level = (average / 255) * 100;
            document.getElementById('audioLevel').style.width = level + '%';
            
            if (!isStreaming) {
                requestAnimationFrame(updateAudioLevel);
            }
        }
        
        updateAudioLevel();
    } catch (error) {
        console.error('Error setting up audio monitoring:', error);
    }
}

function updateStatus(type, text) {
    const indicator = document.getElementById('statusIndicator');
    const statusText = document.getElementById('statusText');
    
    indicator.className = 'status-indicator';
    if (type === 'live') {
        indicator.classList.add('live');
    } else if (type === 'ready') {
        indicator.style.background = '#10b981';
    } else if (type === 'error') {
        indicator.style.background = '#ef4444';
    }
    
    statusText.textContent = text;
}

// Toggle camera
document.getElementById('toggleCamera').addEventListener('click', function() {
    if (localStream) {
        const videoTrack = localStream.getVideoTracks()[0];
        cameraEnabled = !cameraEnabled;
        videoTrack.enabled = cameraEnabled;
        this.classList.toggle('active', !cameraEnabled);
        this.querySelector('i').className = cameraEnabled ? 'fas fa-video' : 'fas fa-video-slash';
    }
});

// Toggle microphone
document.getElementById('toggleMic').addEventListener('click', function() {
    if (localStream) {
        const audioTrack = localStream.getAudioTracks()[0];
        micEnabled = !micEnabled;
        audioTrack.enabled = micEnabled;
        this.classList.toggle('active', !micEnabled);
        this.querySelector('i').className = micEnabled ? 'fas fa-microphone' : 'fas fa-microphone-slash';
    }
});

// Change camera
document.getElementById('cameraSelect').addEventListener('change', async function() {
    const deviceId = this.value;
    if (deviceId && localStream) {
        try {
            const hdQuality = document.getElementById('hdQuality').checked;
            const videoTrack = localStream.getVideoTracks()[0];
            videoTrack.stop();
            
            const newStream = await navigator.mediaDevices.getUserMedia({
                video: {
                    deviceId: { exact: deviceId },
                    width: { ideal: hdQuality ? 1920 : 1280 },
                    height: { ideal: hdQuality ? 1080 : 720 }
                }
            });
            
            localStream.removeTrack(videoTrack);
            localStream.addTrack(newStream.getVideoTracks()[0]);
            document.getElementById('videoPreview').srcObject = localStream;
        } catch (error) {
            console.error('Error changing camera:', error);
        }
    }
});

// Change microphone
document.getElementById('micSelect').addEventListener('change', async function() {
    const deviceId = this.value;
    if (deviceId && localStream) {
        try {
            const audioTrack = localStream.getAudioTracks()[0];
            audioTrack.stop();
            
            const newStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    deviceId: { exact: deviceId },
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });
            
            localStream.removeTrack(audioTrack);
            localStream.addTrack(newStream.getAudioTracks()[0]);
            
            // Reconnect audio monitoring
            if (audioContext && analyser) {
                const source = audioContext.createMediaStreamSource(localStream);
                source.connect(analyser);
            }
        } catch (error) {
            console.error('Error changing microphone:', error);
        }
    }
});

// Go Live button
document.getElementById('goLiveBtn').addEventListener('click', function() {
    if (!isStreaming) {
        startStreaming();
    } else {
        stopStreaming();
    }
});

function startStreaming() {
    const streamTitle = document.getElementById('streamTitle').value.trim();
    if (!streamTitle) {
        alert('Please enter a stream title before going live!');
        return;
    }

    // In production, this would create a stream record in the database
    // For now, we'll simulate with a placeholder stream ID
    // This should be replaced with an API call to create the stream
    currentStreamId = Date.now(); // Placeholder - should come from API
    
    isStreaming = true;
    streamStartTime = Date.now();
    
    // Update UI
    document.getElementById('goLiveBtn').textContent = 'End Stream';
    document.getElementById('goLiveBtn').style.background = '#b91c1c';
    updateStatus('live', '🔴 LIVE');
    
    // Start duration counter
    durationInterval = setInterval(updateDuration, 1000);
    
    // Start fetching stream stats
    statsInterval = setInterval(updateStreamStats, 5000); // Every 5 seconds
    updateStreamStats(); // Initial fetch
    
    // In a real implementation, this would connect to a streaming server
    console.log('Stream started with title:', streamTitle);
    
    // Show success message
    showNotification('🎉 You are now LIVE! Your stream is broadcasting to customers.');
}

function stopStreaming() {
    // Show end stream modal instead of immediate confirmation
    showEndStreamModal();
}

function showEndStreamModal() {
    if (!currentStreamId) {
        alert('No active stream found');
        return;
    }
    
    // Fetch final stats
    fetch(`/api/live/stats.php?stream_id=${currentStreamId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update modal with final stats
                document.getElementById('finalDuration').textContent = formatDuration(data.stats.duration);
                document.getElementById('finalViewers').textContent = data.stats.viewers;
                document.getElementById('finalLikes').textContent = data.stats.likes;
                document.getElementById('finalOrders').textContent = data.stats.orders;
                document.getElementById('finalRevenue').textContent = '$' + data.stats.revenue.toFixed(2);
            }
        })
        .catch(error => console.error('Error fetching final stats:', error));
    
    // Show modal
    document.getElementById('endStreamModal').style.display = 'flex';
}

function endStreamWithAction(action) {
    fetch('/api/live/end-stream.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            stream_id: currentStreamId,
            action: action,
            video_url: 'placeholder_video_url' // In production, this would be the actual stream recording URL
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clean up
            isStreaming = false;
            clearInterval(durationInterval);
            clearInterval(statsInterval);
            
            // Update UI
            document.getElementById('goLiveBtn').textContent = 'Go Live';
            document.getElementById('goLiveBtn').style.background = '#dc2626';
            updateStatus('ready', 'Stream Ended');
            
            // Hide modal
            document.getElementById('endStreamModal').style.display = 'none';
            
            // Show success message
            if (action === 'save') {
                showNotification('✅ Stream saved successfully! Viewers can watch it on-demand.');
            } else {
                showNotification('✅ Stream ended successfully!');
            }
            
            // Redirect to dashboard after a moment
            setTimeout(() => {
                window.location.href = '/seller/live.php';
            }, 2000);
        } else {
            alert('Error ending stream: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to end stream. Please try again.');
    });
}

function cancelEndStream() {
    document.getElementById('endStreamModal').style.display = 'none';
}

function updateStreamStats() {
    if (!isStreaming || !currentStreamId) return;
    
    fetch(`/api/live/stats.php?stream_id=${currentStreamId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update stats display
                document.getElementById('viewerCount').textContent = data.stats.current_viewers;
                document.getElementById('likesCount').textContent = data.stats.likes;
                document.getElementById('dislikesCount').textContent = data.stats.dislikes;
                document.getElementById('commentsCount').textContent = data.stats.comments;
                document.getElementById('ordersCount').textContent = data.stats.orders;
                document.getElementById('revenueAmount').textContent = '$' + data.stats.revenue.toFixed(2);
                
                // Update viewers list
                const viewersList = document.getElementById('viewersList');
                if (data.viewers && data.viewers.length > 0) {
                    viewersList.innerHTML = data.viewers.map(viewer => `
                        <div style="padding: 5px 10px; border-bottom: 1px solid #e5e7eb;">
                            👤 ${viewer.username}
                        </div>
                    `).join('');
                } else {
                    viewersList.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 10px;">No viewers yet</div>';
                }
                
                // Update comments feed
                const commentsFeed = document.getElementById('commentsFeed');
                if (data.comments && data.comments.length > 0) {
                    commentsFeed.innerHTML = data.comments.map(comment => `
                        <div style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">
                            <strong style="color: #0654ba;">${comment.username}:</strong>
                            <div style="color: #4b5563; margin-top: 2px;">${escapeHtml(comment.text)}</div>
                            <div style="color: #9ca3af; font-size: 11px; margin-top: 2px;">${formatTimestamp(comment.created_at)}</div>
                        </div>
                    `).join('');
                    commentsFeed.scrollTop = commentsFeed.scrollHeight;
                } else {
                    commentsFeed.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 10px;">No comments yet</div>';
                }
                
                // Update orders list
                const ordersList = document.getElementById('ordersList');
                if (data.orders && data.orders.length > 0) {
                    ordersList.innerHTML = data.orders.map(order => `
                        <div style="padding: 8px 10px; border-bottom: 1px solid #e5e7eb;">
                            <div style="font-weight: 600; color: #1f2937;">${order.product_name}</div>
                            <div style="color: #6b7280; font-size: 12px;">
                                ${order.username} • $${order.amount.toFixed(2)}
                            </div>
                        </div>
                    `).join('');
                } else {
                    ordersList.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 10px;">No orders yet</div>';
                }
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

function formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    if (hours > 0) {
        return `${hours}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function stopStreaming() {
    if (confirm('Are you sure you want to end your live stream?')) {
        isStreaming = false;
        
        // Update UI
        document.getElementById('goLiveBtn').textContent = 'Go Live';
        document.getElementById('goLiveBtn').style.background = '#dc2626';
        updateStatus('ready', 'Stream Ended');
        
        // Stop duration counter
        if (durationInterval) {
            clearInterval(durationInterval);
            durationInterval = null;
        }
        
        // Reset viewer count
        document.getElementById('viewerCount').textContent = '0';
        
        // Show summary
        const duration = document.getElementById('duration').textContent;
        showNotification(`Stream ended. Duration: ${duration}. Great job! 👏`);
    }
}

function updateDuration() {
    if (!streamStartTime) return;
    
    const elapsed = Math.floor((Date.now() - streamStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    document.getElementById('duration').textContent = 
        `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

function simulateViewers() {
    if (!isStreaming) return;
    
    // Simulate viewers joining
    const currentCount = parseInt(document.getElementById('viewerCount').textContent);
    const change = Math.floor(Math.random() * 5) - 1; // -1 to +3
    const newCount = Math.max(0, currentCount + change);
    document.getElementById('viewerCount').textContent = newCount;
    
    if (isStreaming) {
        setTimeout(simulateViewers, 3000 + Math.random() * 2000);
    }
}

function showNotification(message) {
    // Simple notification (in production, use a proper notification system)
    const notification = document.createElement('div');
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: white; padding: 16px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; max-width: 400px;';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transition = 'opacity 0.3s';
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 5000);
}

// Load featured products
function loadFeaturedProducts() {
    const urlParams = new URLSearchParams(window.location.search);
    const productIds = urlParams.get('products');
    
    if (productIds) {
        // In production, fetch product details from server
        const productsList = document.getElementById('productsList');
        productsList.innerHTML = '<div style="text-align: center; color: #6b7280; padding: 20px;">Loading products...</div>';
        
        // Simulated product display
        setTimeout(() => {
            productsList.innerHTML = `
                <div style="text-align: center; color: #6b7280; padding: 20px;">
                    ${productIds.split(',').length} product(s) selected
                </div>
            `;
        }, 500);
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeStream();
    loadFeaturedProducts();
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (localStream) {
        localStream.getTracks().forEach(track => track.stop());
    }
    if (audioContext) {
        audioContext.close();
    }
});
</script>

<?php
include __DIR__ . '/../templates/footer.php';
?>
