import Hls from 'hls.js';

const video = document.getElementById('hls-player');

if (video) {
    const src = video.dataset.src;

    if (Hls.isSupported()) {
        const hls = new Hls();
        hls.loadSource(src);
        hls.attachMedia(video);
    } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = src;
    }
}
