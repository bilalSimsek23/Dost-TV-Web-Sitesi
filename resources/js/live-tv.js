import Hls from 'hls.js';

const video = document.getElementById('hls-player');
const errorContainer = document.getElementById('hls-error-container');
const errorText = document.getElementById('hls-error-text');

if (video) {
    const primarySrc = video.dataset.src;
    const backupSrc = video.dataset.backupSrc;
    const errorMsg = video.dataset.errorMsg || 'Canlı yayın şu anda yüklenemiyor. Lütfen daha sonra tekrar deneyin.';
    let triedBackup = false;

    function showError() {
        if (errorContainer && errorText) {
            errorText.textContent = errorMsg;
            errorContainer.classList.remove('hidden');
        }
    }

    if (Hls.isSupported()) {
        const hls = new Hls({
            enableWorker: true,
            lowLatencyMode: true,
            backBufferLength: 90,
        });

        hls.loadSource(primarySrc);
        hls.attachMedia(video);

        hls.on(Hls.Events.ERROR, function (_event, data) {
            if (data.fatal) {
                switch (data.type) {
                    case Hls.ErrorTypes.NETWORK_ERROR:
                        if (!triedBackup && backupSrc && backupSrc.trim() !== '') {
                            triedBackup = true;
                            hls.loadSource(backupSrc);
                            hls.startLoad();
                        } else {
                            hls.destroy();
                            showError();
                        }
                        break;
                    case Hls.ErrorTypes.MEDIA_ERROR:
                        hls.recoverMediaError();
                        break;
                    default:
                        if (!triedBackup && backupSrc && backupSrc.trim() !== '') {
                            triedBackup = true;
                            hls.loadSource(backupSrc);
                            hls.startLoad();
                        } else {
                            hls.destroy();
                            showError();
                        }
                        break;
                }
            }
        });
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = primarySrc;

        video.addEventListener('error', function () {
            if (!triedBackup && backupSrc && backupSrc.trim() !== '') {
                triedBackup = true;
                video.src = backupSrc;
                video.load();
                video.play().catch(() => {});
            } else {
                showError();
            }
        });
    }
}
