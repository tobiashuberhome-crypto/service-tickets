(function () {
    function ensureModal() {
        let modal = document.getElementById('scanner-modal');
        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.id = 'scanner-modal';
        modal.style.cssText = 'display:none;position:fixed;inset:0;z-index:1000;background:rgba(16,24,40,.72);align-items:center;justify-content:center;padding:20px;';
        modal.innerHTML = [
            '<div style="width:min(560px,100%);background:#fff;border-radius:8px;border:1px solid #d9dee7;box-shadow:0 24px 70px rgba(0,0,0,.25);overflow:hidden;">',
            '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:14px 16px;border-bottom:1px solid #d9dee7;">',
            '<strong>Code scannen</strong>',
            '<button type="button" id="scanner-close" class="btn secondary">Schliessen</button>',
            '</div>',
            '<div style="padding:16px;display:grid;gap:12px;">',
            '<video id="scanner-video" style="width:100%;max-height:360px;background:#111;border-radius:6px;" muted playsinline></video>',
            '<div id="scanner-status" class="muted">Kamera wird vorbereitet...</div>',
            '<div class="form-row">',
            '<div><label for="scanner-manual-code">Code manuell</label><input id="scanner-manual-code"></div>',
            '<div style="display:flex;align-items:end;"><button type="button" id="scanner-manual-use" class="btn">Uebernehmen</button></div>',
            '</div>',
            '</div>',
            '</div>',
        ].join('');
        document.body.appendChild(modal);

        return modal;
    }

    async function open(options) {
        const modal = ensureModal();
        const video = document.getElementById('scanner-video');
        const status = document.getElementById('scanner-status');
        const close = document.getElementById('scanner-close');
        const manualCode = document.getElementById('scanner-manual-code');
        const manualUse = document.getElementById('scanner-manual-use');

        let stream = null;
        let stopped = false;
        let detector = null;

        const finish = (code) => {
            if (!code) {
                return;
            }

            stop();
            options.onDetected(String(code));
        };

        const stop = () => {
            stopped = true;
            if (stream) {
                stream.getTracks().forEach((track) => track.stop());
            }
            video.srcObject = null;
            modal.style.display = 'none';
        };

        close.onclick = stop;
        manualUse.onclick = () => finish(manualCode.value.trim());
        manualCode.value = '';
        modal.style.display = 'flex';

        if (!('BarcodeDetector' in window) || !navigator.mediaDevices?.getUserMedia) {
            status.textContent = 'Automatisches Scannen wird von diesem Browser nicht unterstuetzt. Bitte Code manuell eingeben.';
            video.style.display = 'none';
            return;
        }

        video.style.display = '';

        try {
            detector = new BarcodeDetector({
                formats: ['qr_code', 'code_128', 'code_39', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'data_matrix', 'itf'],
            });
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'environment' },
                audio: false,
            });
            video.srcObject = stream;
            await video.play();
            status.textContent = 'Code vor die Kamera halten.';
        } catch (error) {
            status.textContent = 'Kamera konnte nicht gestartet werden. Bitte Code manuell eingeben.';
            video.style.display = 'none';
            return;
        }

        async function scanFrame() {
            if (stopped) {
                return;
            }

            try {
                const codes = await detector.detect(video);
                if (codes.length > 0) {
                    finish(codes[0].rawValue);
                    return;
                }
            } catch (error) {
                status.textContent = 'Scan nicht moeglich. Bitte Code manuell eingeben.';
            }

            window.setTimeout(scanFrame, 250);
        }

        scanFrame();
    }

    window.ServiceTicketScanner = { open };
})();
