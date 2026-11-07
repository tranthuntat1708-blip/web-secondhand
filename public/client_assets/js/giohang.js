/* ===== TRANG GIO HANG ===== */

/* ===== MULTI-SELECT & BULK DELETE ===== */

/**
 * Bật/tắt chọn tất cả sản phẩm
 */
function toggleSelectAll(masterCb) {
    const itemCheckboxes = document.querySelectorAll('.item-checkbox');
    itemCheckboxes.forEach(cb => {
        cb.checked = masterCb.checked;
        const row = cb.closest('.cart-item-row');
        if (row) row.classList.toggle('is-selected', masterCb.checked);
    });
    updateBulkBar();
}

/**
 * Gọi khi 1 checkbox đơn lẻ thay đổi
 */
function onItemCheckboxChange() {
    const allCbs  = document.querySelectorAll('.item-checkbox');
    const checkedCbs = document.querySelectorAll('.item-checkbox:checked');
    const master  = document.getElementById('selectAllCheckbox');

    allCbs.forEach(cb => {
        const row = cb.closest('.cart-item-row');
        if (row) row.classList.toggle('is-selected', cb.checked);
    });

    if (master) {
        master.checked       = allCbs.length > 0 && checkedCbs.length === allCbs.length;
        master.indeterminate = checkedCbs.length > 0 && checkedCbs.length < allCbs.length;
    }
    updateBulkBar();
}

/**
 * Cập nhật thanh bulk-action hiện/ẩn và số đếm
 */
function updateBulkBar() {
    const checkedCbs = document.querySelectorAll('.item-checkbox:checked');
    const bar        = document.getElementById('bulkActionBar');
    const countEl    = document.getElementById('selectedCount');

    if (!bar) return;
    if (checkedCbs.length > 0) {
        bar.style.display = 'block';
        if (countEl) countEl.textContent = checkedCbs.length;
    } else {
        bar.style.display = 'none';
    }
}

/**
 * Bỏ chọn tất cả
 */
function clearAllSelections() {
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.checked = false;
        const row = cb.closest('.cart-item-row');
        if (row) row.classList.remove('is-selected');
    });
    const master = document.getElementById('selectAllCheckbox');
    if (master) { master.checked = false; master.indeterminate = false; }
    updateBulkBar();
}

/* ===== DELETE MODAL ===== */

// Lưu danh sách id cần xóa khi xóa nhiều
let _pendingDeleteIds = [];

/**
 * Mở modal xác nhận xóa 1 sản phẩm
 */
function confirmDeleteSingle(productId, productName) {
    _pendingDeleteIds = [productId];
    const desc = document.getElementById('deleteModalDesc');
    if (desc) desc.innerHTML = 'Bạn có chắc muốn xóa <strong>' + productName + '</strong> khỏi giỏ hàng?';
    const link = document.getElementById('deleteConfirmLink');
    if (link) link.href = 'index.php?action=giohang&remove_id=' + productId;
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) modal.style.display = 'flex';
}

/**
 * Mở modal xác nhận xóa nhiều sản phẩm đã chọn
 */
function confirmDeleteSelected() {
    const checkedCbs = document.querySelectorAll('.item-checkbox:checked');
    if (checkedCbs.length === 0) return;
    _pendingDeleteIds = Array.from(checkedCbs).map(cb => cb.value);

    const desc = document.getElementById('deleteModalDesc');
    if (desc) desc.innerHTML = 'Bạn có chắc muốn xóa <strong>' + _pendingDeleteIds.length + ' sản phẩm</strong> đã chọn khỏi giỏ hàng?';

    // Xây URL xóa nhiều (gửi qua query string ids[])
    const ids = _pendingDeleteIds.map(id => 'remove_ids[]=' + encodeURIComponent(id)).join('&');
    const link = document.getElementById('deleteConfirmLink');
    if (link) link.href = 'index.php?action=giohang&' + ids;

    const modal = document.getElementById('deleteConfirmModal');
    if (modal) modal.style.display = 'flex';
}

/**
 * Đóng modal xóa
 */
function closeDeleteModal(event) {
    if (event && event.target !== document.getElementById('deleteConfirmModal')) return;
    const modal = document.getElementById('deleteConfirmModal');
    if (modal) modal.style.display = 'none';
    _pendingDeleteIds = [];
}

// Đóng modal bằng phím Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('deleteConfirmModal');
        if (modal && modal.style.display !== 'none') {
            modal.style.display = 'none';
            _pendingDeleteIds = [];
        }
    }
});

function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN').format(number);
}

function calculateTotals() {
    let subtotal = 0;
    const rows = document.querySelectorAll('.cart-item-row');
    
    rows.forEach(row => {
        const input = row.querySelector('input[type="number"]');
        const priceStr = row.getAttribute('data-price');
        if (input && priceStr) {
            let qty = parseInt(input.value) || 1;
            let price = parseInt(priceStr) || 0;
            let itemTotal = qty * price;
            subtotal += itemTotal;
            
            // Update item total display
            const itemTotalEl = row.querySelector('.item-total-price');
            if (itemTotalEl) {
                itemTotalEl.innerText = formatCurrency(itemTotal) + ' đ';
            }
        }
    });

    let discount = 0;
    if (typeof couponData !== 'undefined' && couponData.muc_giam > 0) {
        if (couponData.loai_giam === 'phan_tram') {
            discount = (subtotal * couponData.muc_giam) / 100;
            if (couponData.giam_toi_da > 0 && discount > couponData.giam_toi_da) {
                discount = couponData.giam_toi_da;
            }
        } else {
            discount = couponData.muc_giam;
        }
        if (discount > subtotal) discount = subtotal;
    }

    let finalTotal = subtotal - discount;

    // Update displays
    const subtotalEl = document.getElementById('cart-subtotal');
    if (subtotalEl) subtotalEl.innerText = formatCurrency(subtotal);

    const discountEl = document.getElementById('cart-discount');
    if (discountEl) discountEl.innerText = formatCurrency(discount);

    const finalTotalEl = document.getElementById('cart-final-total');
    if (finalTotalEl) finalTotalEl.innerText = formatCurrency(finalTotal);
}

function checkAllLimits() {
    let hasError = false;
    const inputs = document.querySelectorAll('.cart-container input[type="number"]');
    if (inputs.length === 0) return;

    inputs.forEach(input => {
        let maxVal = parseInt(input.getAttribute('max')) || 3;
        let currentVal = parseInt(input.value) || 1;
        const container = input.parentElement.parentElement;
        const errorSpan = container.querySelector('.qty-error');
        
        if (currentVal > maxVal) {
            hasError = true;
            if(errorSpan) {
                errorSpan.innerText = "Giới hạn mua tối đa hoặc kho không đủ!";
                errorSpan.style.display = 'block';
            }
        } else {
            if(errorSpan) {
                errorSpan.style.display = 'none';
            }
        }
    });

    const btnCheckout = document.getElementById('btn-checkout');
    if (btnCheckout) {
        if (hasError) {
            btnCheckout.style.pointerEvents = 'none';
            btnCheckout.style.opacity = '0.5';
            btnCheckout.style.cursor = 'not-allowed';
            btnCheckout.innerText = 'VUI LÒNG KIỂM TRA LẠI SỐ LƯỢNG';
        } else {
            btnCheckout.style.pointerEvents = 'auto';
            btnCheckout.style.opacity = '1';
            btnCheckout.style.cursor = 'pointer';
            btnCheckout.innerText = 'TIẾN HÀNH ĐẶT HÀNG';
        }
    }
    
    // Call calculation to update UI totals dynamically
    calculateTotals();
}

function changeQty(btn, delta) {
    const container = btn.parentElement.parentElement;
    const input = container.querySelector('input[type="number"]');
    if (!input) return;

    let currentVal = parseInt(input.value) || 1;
    let newVal = currentVal + delta;
    if (newVal < 1) newVal = 1;
    input.value = newVal;
    
    checkAllLimits();
}

function validateQty(input) {
    // Cho phép ô trống trong khi đang nhập (để người dùng xóa rồi gõ số mới)
    // Chỉ loại bỏ ký tự không phải số (không phải số âm, không phải dấu chấm)
    let val = input.value.replace(/[^0-9]/g, '');
    input.value = val;

    // Nếu đã có giá trị và < 1 thì đặt về 1
    if (val !== '' && parseInt(val) < 1) {
        input.value = 1;
    }

    checkAllLimits();
}

function onQtyBlur(input) {
    // Khi người dùng click ra ngoài: nếu ô trống hoặc giá trị không hợp lệ thì về 1
    let val = input.value.trim();
    if (val === '' || isNaN(parseInt(val)) || parseInt(val) < 1) {
        input.value = 1;
    } else {
        input.value = parseInt(val);
    }
    checkAllLimits();
}

// Chạy kiểm tra ngay khi load trang
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.cart-container')) {
        checkAllLimits();
    }
});

function blockInvalidKeys(e) {
    // Chặn phím: dấu trừ, dấu chấm, chữ e
    if (['e', 'E', '-', '+', '.'].includes(e.key)) {
        e.preventDefault();
    }
}

/* ===== THANH TOAN ===== */
function chonThanhToan(el, loai) {
    // Bỏ active tất cả
    document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
    document.querySelectorAll('.payment-detail-box').forEach(p => p.classList.remove('show'));
    
    // Kích hoạt cái đang chọn
    el.classList.add('active');
    const panel = document.getElementById('panel-' + loai);
    if (panel) panel.classList.add('show');
}

/* ===== DON HANG USER ===== */
function openReturnModal(id, name, phone, address) {
    const returnId = document.getElementById('return_donhang_id');
    const returnName = document.getElementById('return_name');
    const returnPhone = document.getElementById('return_phone');
    const returnAddr = document.getElementById('return_address');
    const modal = document.getElementById('returnModal');

    if (returnId) returnId.value = id;
    if (returnName) returnName.value = name;
    if (returnPhone) returnPhone.value = phone;
    if (returnAddr) returnAddr.value = address;
    if (modal) modal.style.display = 'flex';
}

function closeReturnModal() {
    const modal = document.getElementById('returnModal');
    if (modal) {
        modal.style.display = 'none';
        // Reset toàn bộ khi đóng modal
        const previewContainer = document.getElementById('mediaPreviewContainer');
        if (previewContainer) {
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'none';
        }
        const picker = document.getElementById('minhchung_file_picker');
        if (picker) picker.value = '';
        const hiddenInput = document.getElementById('minhchung_files_input');
        if (hiddenInput) {
            // Xóa DataTransfer
            const dt = new DataTransfer();
            hiddenInput.files = dt.files;
        }
    }
}

/* ===== TÍCH LŨY ẢNH / VIDEO KHI UPLOAD NHIỀU LẦN ===== */
// Lưu trữ danh sách file đã chọn tích lũy
let _accumulatedFiles = [];

function accumulateFiles(event) {
    const newFiles = Array.from(event.target.files);
    const previewContainer = document.getElementById('mediaPreviewContainer');
    const hiddenInput = document.getElementById('minhchung_files_input');
    const MAX_SIZE = 10 * 1024 * 1024; // 10MB

    newFiles.forEach(function(file) {
        // Kiểm tra trùng tên file
        const alreadyAdded = _accumulatedFiles.some(f => f.name === file.name && f.size === file.size);
        if (alreadyAdded) return;

        // Kiểm tra kích thước
        if (file.size > MAX_SIZE) {
            alert('File "' + file.name + '" vượt quá 10MB, bỏ qua!');
            return;
        }

        _accumulatedFiles.push(file);

        // Tạo preview item
        const itemDiv = document.createElement('div');
        itemDiv.style.cssText = 'display:flex; align-items:center; gap:10px; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:8px 12px; margin-bottom:8px;';
        itemDiv.dataset.filename = file.name;

        const reader = new FileReader();
        reader.onload = function(e) {
            let thumb;
            if (file.type.startsWith('image/')) {
                thumb = document.createElement('img');
                thumb.src = e.target.result;
                thumb.style.cssText = 'width:60px; height:60px; object-fit:cover; border-radius:6px; flex-shrink:0;';
            } else {
                thumb = document.createElement('video');
                thumb.src = e.target.result;
                thumb.style.cssText = 'width:60px; height:60px; object-fit:cover; border-radius:6px; flex-shrink:0;';
            }
            itemDiv.insertBefore(thumb, itemDiv.firstChild);
        };
        reader.readAsDataURL(file);

        // Tên file
        const nameSpan = document.createElement('span');
        nameSpan.style.cssText = 'flex:1; font-size:0.82rem; color:#334155; word-break:break-all;';
        nameSpan.textContent = file.name;

        // Kích thước
        const sizeSpan = document.createElement('span');
        sizeSpan.style.cssText = 'font-size:0.75rem; color:#94a3b8; flex-shrink:0;';
        sizeSpan.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';

        // Nút xóa
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.innerHTML = '<i class="fa fa-times"></i>';
        removeBtn.style.cssText = 'background:#fee2e2; color:#dc2626; border:none; border-radius:6px; padding:4px 8px; cursor:pointer; font-size:0.85rem; flex-shrink:0;';
        removeBtn.onclick = function() {
            // Xóa khỏi danh sách tích lũy
            _accumulatedFiles = _accumulatedFiles.filter(f => !(f.name === file.name && f.size === file.size));
            itemDiv.remove();
            syncFilesToInput();
            if (_accumulatedFiles.length === 0) {
                previewContainer.style.display = 'none';
            }
        };

        itemDiv.appendChild(nameSpan);
        itemDiv.appendChild(sizeSpan);
        itemDiv.appendChild(removeBtn);
        previewContainer.appendChild(itemDiv);
        previewContainer.style.display = 'block';
    });

    // Đặt lại picker để có thể chọn cùng file lại nếu muốn
    event.target.value = '';
    syncFilesToInput();
}

function syncFilesToInput() {
    // Đồng bộ _accumulatedFiles vào hidden input để form submit được
    const hiddenInput = document.getElementById('minhchung_files_input');
    if (!hiddenInput) return;
    const dt = new DataTransfer();
    _accumulatedFiles.forEach(f => dt.items.add(f));
    hiddenInput.files = dt.files;
}

// Reset khi mở modal mới
const _origOpenReturnModal = window.openReturnModal;
window.openReturnModal = function(id, name, phone, address) {
    _accumulatedFiles = [];
    const previewContainer = document.getElementById('mediaPreviewContainer');
    if (previewContainer) { previewContainer.innerHTML = ''; previewContainer.style.display = 'none'; }
    const picker = document.getElementById('minhchung_file_picker');
    if (picker) picker.value = '';
    const hiddenInput = document.getElementById('minhchung_files_input');
    if (hiddenInput) { const dt = new DataTransfer(); hiddenInput.files = dt.files; }

    const returnId = document.getElementById('return_donhang_id');
    const returnName = document.getElementById('return_name');
    const returnPhone = document.getElementById('return_phone');
    const returnAddr = document.getElementById('return_address');
    const modal = document.getElementById('returnModal');
    if (returnId) returnId.value = id;
    if (returnName) returnName.value = name;
    if (returnPhone) returnPhone.value = phone;
    if (returnAddr) returnAddr.value = address;
    if (modal) modal.style.display = 'flex';
};
