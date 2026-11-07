/* JS cho Admin */
function editCategory(id, ten, parent_id) {
    const suaId = document.getElementById('sua_id');
    const suaTen = document.getElementById('sua_ten');
    const suaParent = document.getElementById('sua_parent');
    const modalSua = document.getElementById('modalSua');

    if (suaId) suaId.value = id;
    if (suaTen) suaTen.value = ten;
    if (suaParent) suaParent.value = parent_id ? parent_id : '';
    if (modalSua) modalSua.style.display = 'flex';
}
