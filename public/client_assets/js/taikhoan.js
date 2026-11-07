function togglePwd(id) {
    const input = document.getElementById(id);
    if (input && input.type === 'password') {
        input.type = 'text';
    } else if (input) {
        input.type = 'password';
    }
}

function kiemTraMatKhau() {
    const pwd = document.getElementById('pwd_reg')?.value;
    const confirm = document.getElementById('pwd_confirm')?.value;
    const errorMsg = document.getElementById('error-msg');
    
    if (pwd !== confirm) {
        if(errorMsg) errorMsg.style.display = 'block';
        return false;
    }
    if(errorMsg) errorMsg.style.display = 'none';
    return true;
}

function batDauSua() {
    // Bật các input
    document.querySelectorAll('.profile-input').forEach(function(input) {
        input.disabled = false;
    });
    // Đổi nút
    const btnEdit = document.getElementById('btnEdit');
    const btnSave = document.getElementById('btnSave');
    const btnCancel = document.getElementById('btnCancel');
    if(btnEdit) btnEdit.style.display = 'none';
    if(btnSave) btnSave.style.display = 'inline-flex';
    if(btnCancel) btnCancel.style.display = 'inline-flex';
}

function huyBo() {
    // Tắt các input + khôi phục giá trị gốc
    document.querySelectorAll('.profile-input').forEach(function(input) {
        input.disabled = true;
    });
    // Đổi nút
    const btnEdit = document.getElementById('btnEdit');
    const btnSave = document.getElementById('btnSave');
    const btnCancel = document.getElementById('btnCancel');
    if(btnEdit) btnEdit.style.display = 'inline-flex';
    if(btnSave) btnSave.style.display = 'none';
    if(btnCancel) btnCancel.style.display = 'none';
    
    // Reset form về giá trị ban đầu
    const profileForm = document.getElementById('profileForm');
    if(profileForm) profileForm.reset();
}
