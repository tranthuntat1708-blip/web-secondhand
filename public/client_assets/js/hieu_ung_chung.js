/* =======================================================
   HIỆU ỨNG CHUNG CHO CLIENT PANEL
   File: client_assets/js/hieu_ung_chung.js
   ======================================================= */

document.addEventListener('DOMContentLoaded', function() {

    // --- 1. MODAL LIÊN HỆ QR CODE ---
    const qrModal = document.getElementById('qrModal');
    const qrTitle = document.getElementById('qrTitle');
    const qrImage = document.getElementById('qrImage');

    window.openQRModal = function(type) {
        if(type === 'zalo') {
            qrTitle.innerHTML = 'Liên Hệ Zalo';
            qrImage.src = './public/client_assets/images/qr_zalo.jpg'; 
        } else {
            qrTitle.innerHTML = 'Liên Hệ Messenger';
            qrImage.src = './public/client_assets/images/qr_facebook.jpg';
        }
        qrModal.style.display = 'flex';
    };

    window.closeQRModal = function() {
        qrModal.style.display = 'none';
    };

    // --- 2. MODAL YÊU CẦU ĐĂNG NHẬP (AUTH) ---
    const authModal = document.getElementById('authModal');

    window.openAuthModal = function() {
        if (authModal) authModal.style.display = 'flex';
    };

    window.closeAuthModal = function() {
        if (authModal) authModal.style.display = 'none';
    };

    // --- 3. ĐÓNG MODAL KHI CLICK RA NGOÀI ---
    window.addEventListener('click', function(event) {
        if (event.target === qrModal) {
            closeQRModal();
        }
        if (event.target === authModal) {
            closeAuthModal();
        }
    });

    // --- 4. TỰ ĐỘNG ẨN TOAST THÔNG BÁO ---
    const cartToast = document.getElementById('cartToast');
    if (cartToast) {
        setTimeout(function() {
            cartToast.style.animation = "fadeOut 0.4s forwards";
            setTimeout(function() { cartToast.remove(); }, 400);
        }, 1500);
    }


    // --- 6. SCROLL REVEAL CHO PRODUCT CARDS ---
    const revealCards = document.querySelectorAll('.product-card');
    if (revealCards.length > 0 && 'IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    entry.target.classList.remove('is-hidden');
                } else {
                    entry.target.classList.remove('is-visible');
                    entry.target.classList.add('is-hidden');
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });
        revealCards.forEach(function(card) {
            revealObserver.observe(card);
        });
    } else {
        // Fallback: hien thi ngay neu khong ho tro IntersectionObserver
        revealCards.forEach(function(card) {
            card.classList.add('is-visible');
        });
    }

});

    // --- 5. X�A BI?N MSG TRONG URL ---
    if (window.history.replaceState) {
        const url = new URL(window.location);
        if(url.searchParams.has('msg')) {
            url.searchParams.delete('msg');
            window.history.replaceState(null, null, url);
        }
    }

