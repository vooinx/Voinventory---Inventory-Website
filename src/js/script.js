document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById('container');
    const registerBtn = document.getElementById('register');
    const loginBtn = document.getElementById('login');

    registerBtn.addEventListener('click', () => {
        container.classList.add("active");
    });

    loginBtn.addEventListener('click', () => {
        container.classList.remove("active");
    });

    // Sign In Toggle
    const toggleSigninPassword = document.getElementById("toggleSigninPassword");
    const signinPassword = document.getElementById("signinPassword");

    if (toggleSigninPassword && signinPassword) {
        toggleSigninPassword.addEventListener("click", function () {
            const type = signinPassword.getAttribute("type") === "password" ? "text" : "password";
            signinPassword.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
            this.classList.toggle("fa-eye");
        });
    }

    // Sign Up Toggle
    const toggleSignupPassword = document.getElementById("toggleSignupPassword");
    const signupPassword = document.getElementById("signupPassword");

    if (toggleSignupPassword && signupPassword) {
        toggleSignupPassword.addEventListener("click", function () {
            const type = signupPassword.getAttribute("type") === "password" ? "text" : "password";
            signupPassword.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
            this.classList.toggle("fa-eye");
        });
    }
});


// search value on table
document.getElementById('tableSearch').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.getElementById('stockTable');
        const rows = table.getElementsByTagName('tr');
            
        for(let i = 1; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            let found = false;
                
            for(let j = 0; j < cells.length - 1; j++) {
                if(cells[j].textContent.toLowerCase().includes(searchValue)) {
                    found = true;
                    break;
                }
            }
                
        row.style.display = found ? '' : 'none';
    }
});


// Modal functions
function openAddModal() {
    document.getElementById('tambahBarangModal').style.display = 'block';
}

function openEditModal(id_stok, nama_barang, kategori, pemilik_barang, no_telp, keterangan, stok) {
    document.getElementById('edit_old_id').value = id_stok;
    document.getElementById('edit_id_stok').value = id_stok;
    document.getElementById('edit_nama_barang').value = nama_barang;
    document.getElementById('edit_kategori').value = kategori;
    document.getElementById('edit_pemilik_barang').value = pemilik_barang;
    document.getElementById('edit_no_telp').value = no_telp;
    document.getElementById('edit_keterangan').value = keterangan;
    document.getElementById('edit_stok').value = stok;

    document.getElementById('editBarangModal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const tambahModal = document.getElementById('tambahBarangModal');
    const editModal = document.getElementById('editBarangModal');
    const keteranganModal = document.getElementById('keteranganModal');

    if (event.target == tambahModal) {
        tambahModal.style.display = 'none';
    }
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
    if (event.target == keteranganModal) {
        keteranganModal.style.display = 'none';
    }
}

// Delete function
function deleteItem(id) {
    if(confirm('Apakah Anda yakin ingin menghapus barang ini?')) {
        window.location.href = 'index.php?delete=' + id;
    }
}

// Function untuk menampilkan keterangan
function viewKeterangan(keterangan, namaBarang) {
document.getElementById('keterangan-title').textContent = namaBarang;
    // Jika keterangan kosong, tampilkan pesan
    if (!keterangan || keterangan.trim() === '') {
        document.getElementById('keterangan-content').innerHTML = '<em style="color: #666;">Tidak ada keterangan untuk barang ini.</em>';
    } else {
        document.getElementById('keterangan-content').textContent = keterangan;
    }
        document.getElementById('keteranganModal').style.display = 'block';
}


// Logout confirmation
function confirmLogout() {
    if (confirm("Apakah Anda yakin ingin keluar dari sistem?")) {
        window.location.href = "logout.php";
    }
}

document.getElementById('entriesSelect')?.addEventListener('change', function() {
    const limit = parseInt(this.value);
    const rows = document.querySelectorAll('#stockTable tbody tr');
    
    rows.forEach((row, index) => {
        row.style.display = (limit === 999 || index < limit) ? '' : 'none';
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('#stockTable tbody tr');
    rows.forEach((row, index) => {
        row.style.display = index < 10 ? '' : 'none';
    });
});