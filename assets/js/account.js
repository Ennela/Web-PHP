/**
 * Account Management - JavaScript Controller
 * Handles: Tab switching, AJAX, validation, toasts, modals, avatar, sizes
 */
(function () {
    'use strict';

    const BASE = document.querySelector('meta[name="base-url"]')?.content || '/WEB-PHP/';

    // ============================================================
    // TOAST NOTIFICATION SYSTEM
    // ============================================================
    const Toast = {
        container: null,
        init() {
            this.container = document.createElement('div');
            this.container.className = 'toast-container';
            document.body.appendChild(this.container);
        },
        show(message, type = 'success') {
            if (!this.container) this.init();
            const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            const toast = document.createElement('div');
            toast.className = `toast-item toast-${type}`;
            toast.innerHTML = `<i class="${icon} toast-icon"></i><span>${message}</span>`;
            this.container.appendChild(toast);
            setTimeout(() => {
                toast.classList.add('toast-out');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        },
        success(msg) { this.show(msg, 'success'); },
        error(msg) { this.show(msg, 'error'); }
    };

    // ============================================================
    // TAB SWITCHING
    // ============================================================
    const navItems = document.querySelectorAll('.sidebar-nav-item[data-tab]');
    const tabContents = document.querySelectorAll('.account-tab-content');

    function switchTab(tabId) {
        navItems.forEach(n => n.classList.remove('active'));
        tabContents.forEach(t => t.classList.remove('active'));

        const activeNav = document.querySelector(`.sidebar-nav-item[data-tab="${tabId}"]`);
        const activeTab = document.getElementById(tabId);

        if (activeNav) activeNav.classList.add('active');
        if (activeTab) {
            activeTab.classList.add('active');
            // Re-trigger animation
            activeTab.style.animation = 'none';
            activeTab.offsetHeight;
            activeTab.style.animation = '';
        }

        // Close mobile sidebar
        document.querySelector('.account-sidebar')?.classList.remove('open');
        document.querySelector('.sidebar-overlay')?.classList.remove('show');

        // Update URL hash
        history.replaceState(null, '', '#' + tabId);
    }

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            switchTab(item.dataset.tab);
        });
    });

    // Load from hash
    const hash = window.location.hash.replace('#', '');
    if (hash && document.getElementById(hash)) {
        switchTab(hash);
    }

    // ============================================================
    // MOBILE SIDEBAR TOGGLE
    // ============================================================
    const mobileToggle = document.getElementById('mobileAccountToggle');
    const sidebar = document.querySelector('.account-sidebar');
    const overlay = document.querySelector('.sidebar-overlay');

    if (mobileToggle) {
        mobileToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('show');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
        });
    }

    // ============================================================
    // PROFILE: EDIT / SAVE / CANCEL
    // ============================================================
    const profileForm = document.getElementById('profileForm');
    const btnEditProfile = document.getElementById('btnEditProfile');
    const btnSaveProfile = document.getElementById('btnSaveProfile');
    const btnCancelProfile = document.getElementById('btnCancelProfile');
    const profileInputs = profileForm ? profileForm.querySelectorAll('.acc-form-input, .acc-form-select') : [];
    let originalProfileValues = {};

    function setProfileEditable(editable) {
        profileInputs.forEach(inp => {
            if (inp.dataset.alwaysReadonly) return;
            if (editable) {
                inp.removeAttribute('disabled');
                inp.removeAttribute('readonly');
            } else {
                inp.setAttribute('disabled', 'disabled');
            }
        });
        if (btnEditProfile) btnEditProfile.style.display = editable ? 'none' : 'inline-flex';
        if (btnSaveProfile) btnSaveProfile.style.display = editable ? 'inline-flex' : 'none';
        if (btnCancelProfile) btnCancelProfile.style.display = editable ? 'inline-flex' : 'none';
    }

    function saveOriginalValues() {
        originalProfileValues = {};
        profileInputs.forEach(inp => {
            originalProfileValues[inp.name] = inp.value;
        });
    }

    function restoreOriginalValues() {
        profileInputs.forEach(inp => {
            if (originalProfileValues.hasOwnProperty(inp.name)) {
                inp.value = originalProfileValues[inp.name];
            }
        });
    }

    if (btnEditProfile) {
        btnEditProfile.addEventListener('click', () => {
            saveOriginalValues();
            setProfileEditable(true);
        });
    }

    if (btnCancelProfile) {
        btnCancelProfile.addEventListener('click', () => {
            restoreOriginalValues();
            setProfileEditable(false);
            // Clear errors
            profileForm.querySelectorAll('.acc-form-error').forEach(e => e.style.display = 'none');
            profileForm.querySelectorAll('.is-error').forEach(e => e.classList.remove('is-error'));
        });
    }

    if (btnSaveProfile) {
        btnSaveProfile.addEventListener('click', () => {
            // Client-side validation
            let valid = true;
            const hoten = profileForm.querySelector('[name="hoten"]');
            const email = profileForm.querySelector('[name="email"]');
            const sdt = profileForm.querySelector('[name="sdt"]');

            // Clear old errors
            profileForm.querySelectorAll('.acc-form-error').forEach(e => e.style.display = 'none');
            profileForm.querySelectorAll('.is-error').forEach(e => e.classList.remove('is-error'));

            if (!hoten.value.trim()) {
                showFieldError(hoten, 'Họ tên không được để trống');
                valid = false;
            }

            if (email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
                showFieldError(email, 'Email không đúng định dạng');
                valid = false;
            }

            if (sdt.value && !/^(0|\+84)[0-9]{9,10}$/.test(sdt.value)) {
                showFieldError(sdt, 'SĐT không hợp lệ (10-11 số)');
                valid = false;
            }

            if (!valid) return;

            // Send AJAX
            const formData = new FormData(profileForm);
            btnSaveProfile.disabled = true;
            btnSaveProfile.innerHTML = '<span class="acc-spinner"></span> Đang lưu...';

            fetch(BASE + 'auth/api_capnhat_thongtin.php', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Toast.success(data.message);
                        setProfileEditable(false);
                        // Update sidebar name
                        const sidebarName = document.querySelector('.sidebar-name');
                        if (sidebarName && data.data.hoten) sidebarName.textContent = data.data.hoten;
                        const sidebarEmail = document.querySelector('.sidebar-email');
                        if (sidebarEmail && data.data.email) sidebarEmail.textContent = data.data.email;
                    } else {
                        Toast.error(data.message);
                    }
                })
                .catch(() => Toast.error('Lỗi kết nối server'))
                .finally(() => {
                    btnSaveProfile.disabled = false;
                    btnSaveProfile.innerHTML = '<i class="fas fa-save"></i> Lưu thay đổi';
                });
        });
    }

    function showFieldError(field, message) {
        field.classList.add('is-error');
        const errEl = field.parentElement.querySelector('.acc-form-error');
        if (errEl) {
            errEl.textContent = message;
            errEl.style.display = 'block';
        }
    }

    // ============================================================
    // AVATAR UPLOAD
    // ============================================================
    const avatarInput = document.getElementById('avatarInput');
    const avatarWrap = document.getElementById('avatarWrap');

    if (avatarWrap && avatarInput) {
        avatarWrap.addEventListener('click', () => avatarInput.click());
        avatarInput.addEventListener('change', () => {
            const file = avatarInput.files[0];
            if (!file) return;

            // Validate client
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (!allowedTypes.includes(file.type)) {
                Toast.error('Chỉ chấp nhận file JPG, PNG, WebP, GIF');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                Toast.error('File quá lớn. Tối đa 2MB');
                return;
            }

            // Preview
            const reader = new FileReader();
            reader.onload = (e) => {
                document.querySelectorAll('.avatar-img').forEach(img => img.src = e.target.result);
            };
            reader.readAsDataURL(file);

            // Upload
            const fd = new FormData();
            fd.append('avatar', file);

            fetch(BASE + 'auth/api_upload_avatar.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Toast.success(data.message);
                        document.querySelectorAll('.avatar-img').forEach(img => img.src = data.avatar_url);
                    } else {
                        Toast.error(data.message);
                    }
                })
                .catch(() => Toast.error('Lỗi upload avatar'));
        });
    }

    // ============================================================
    // CHANGE PASSWORD
    // ============================================================
    const pwForm = document.getElementById('passwordForm');
    const btnChangePw = document.getElementById('btnChangePassword');

    // Toggle visibility
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const input = btn.previousElementSibling;
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    });

    // Password strength
    const newPwInput = document.getElementById('matkhau_moi');
    if (newPwInput) {
        newPwInput.addEventListener('input', () => {
            const val = newPwInput.value;
            const bar = document.querySelector('.password-strength-bar');
            const text = document.querySelector('.password-strength-text');
            if (!bar || !text) return;

            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val) && /[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            bar.className = 'password-strength-bar';
            if (val.length === 0) {
                bar.style.width = '0';
                text.textContent = '';
            } else if (strength <= 1) {
                bar.classList.add('weak');
                text.textContent = 'Yếu';
                text.style.color = '#E53935';
            } else if (strength <= 2) {
                bar.classList.add('medium');
                text.textContent = 'Trung bình';
                text.style.color = '#FF9800';
            } else {
                bar.classList.add('strong');
                text.textContent = 'Mạnh';
                text.style.color = '#43A047';
            }
        });
    }

    if (btnChangePw && pwForm) {
        btnChangePw.addEventListener('click', () => {
            const current = pwForm.querySelector('[name="matkhau_hientai"]').value;
            const newPw = pwForm.querySelector('[name="matkhau_moi"]').value;
            const confirm = pwForm.querySelector('[name="matkhau_xacnhan"]').value;

            // Clear errors
            pwForm.querySelectorAll('.acc-form-error').forEach(e => e.style.display = 'none');
            pwForm.querySelectorAll('.is-error').forEach(e => e.classList.remove('is-error'));

            let valid = true;
            if (!current) {
                showFieldError(pwForm.querySelector('[name="matkhau_hientai"]'), 'Không được để trống');
                valid = false;
            }
            if (!newPw || newPw.length < 6) {
                showFieldError(pwForm.querySelector('[name="matkhau_moi"]'), 'Tối thiểu 6 ký tự');
                valid = false;
            }
            if (newPw !== confirm) {
                showFieldError(pwForm.querySelector('[name="matkhau_xacnhan"]'), 'Mật khẩu xác nhận không khớp');
                valid = false;
            }
            if (!valid) return;

            const fd = new FormData(pwForm);
            btnChangePw.disabled = true;
            btnChangePw.innerHTML = '<span class="acc-spinner"></span> Đang xử lý...';

            fetch(BASE + 'auth/api_doimatkhau.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Toast.success(data.message);
                        pwForm.reset();
                        document.querySelector('.password-strength-bar').className = 'password-strength-bar';
                        document.querySelector('.password-strength-bar').style.width = '0';
                        document.querySelector('.password-strength-text').textContent = '';
                    } else {
                        Toast.error(data.message);
                    }
                })
                .catch(() => Toast.error('Lỗi kết nối server'))
                .finally(() => {
                    btnChangePw.disabled = false;
                    btnChangePw.innerHTML = '<i class="fas fa-key"></i> Đổi mật khẩu';
                });
        });
    }

    // ============================================================
    // ADDRESS BOOK
    // ============================================================
    const addressGrid = document.getElementById('addressGrid');
    const addressModal = document.getElementById('addressModal');
    const addressForm = document.getElementById('addressForm');
    const btnAddAddress = document.getElementById('btnAddAddress');
    let editingAddressId = null;

    // Province/District/Ward API
    const PROVINCE_API = 'https://provinces.open-api.vn/api/';

    async function loadProvinces(selectEl, selectedName) {
        try {
            const res = await fetch(PROVINCE_API + '?depth=1');
            const data = await res.json();
            selectEl.innerHTML = '<option value="">-- Chọn Tỉnh/TP --</option>';
            data.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.name;
                opt.dataset.code = p.code;
                opt.textContent = p.name;
                if (selectedName && p.name === selectedName) opt.selected = true;
                selectEl.appendChild(opt);
            });
        } catch (e) {
            selectEl.innerHTML = '<option value="">Lỗi tải tỉnh/TP</option>';
        }
    }

    async function loadDistricts(provinceCode, selectEl, selectedName) {
        if (!provinceCode) {
            selectEl.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
            return;
        }
        try {
            const res = await fetch(PROVINCE_API + 'p/' + provinceCode + '?depth=2');
            const data = await res.json();
            selectEl.innerHTML = '<option value="">-- Chọn Quận/Huyện --</option>';
            (data.districts || []).forEach(d => {
                const opt = document.createElement('option');
                opt.value = d.name;
                opt.dataset.code = d.code;
                opt.textContent = d.name;
                if (selectedName && d.name === selectedName) opt.selected = true;
                selectEl.appendChild(opt);
            });
        } catch (e) {
            selectEl.innerHTML = '<option value="">Lỗi tải quận/huyện</option>';
        }
    }

    async function loadWards(districtCode, selectEl, selectedName) {
        if (!districtCode) {
            selectEl.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            return;
        }
        try {
            const res = await fetch(PROVINCE_API + 'd/' + districtCode + '?depth=2');
            const data = await res.json();
            selectEl.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
            (data.wards || []).forEach(w => {
                const opt = document.createElement('option');
                opt.value = w.name;
                opt.textContent = w.name;
                if (selectedName && w.name === selectedName) opt.selected = true;
                selectEl.appendChild(opt);
            });
        } catch (e) {
            selectEl.innerHTML = '<option value="">Lỗi tải phường/xã</option>';
        }
    }

    // Province change handler
    const modalTinh = document.getElementById('modal_tinh');
    const modalQuan = document.getElementById('modal_quan');
    const modalPhuong = document.getElementById('modal_phuong');

    if (modalTinh) {
        modalTinh.addEventListener('change', () => {
            const selected = modalTinh.options[modalTinh.selectedIndex];
            const code = selected?.dataset?.code || '';
            loadDistricts(code, modalQuan, '');
            modalPhuong.innerHTML = '<option value="">-- Chọn Phường/Xã --</option>';
        });
    }

    if (modalQuan) {
        modalQuan.addEventListener('change', () => {
            const selected = modalQuan.options[modalQuan.selectedIndex];
            const code = selected?.dataset?.code || '';
            loadWards(code, modalPhuong, '');
        });
    }

    function openAddressModal(address = null) {
        editingAddressId = address ? address.id : null;
        const title = addressModal.querySelector('.acc-modal-header h3');
        title.textContent = address ? 'Sửa địa chỉ' : 'Thêm địa chỉ mới';

        // Reset form
        addressForm.reset();

        // Load provinces
        loadProvinces(modalTinh, address?.tinh || '').then(() => {
            if (address && address.tinh) {
                // find province code and load districts
                const opt = Array.from(modalTinh.options).find(o => o.value === address.tinh);
                if (opt) {
                    loadDistricts(opt.dataset.code, modalQuan, address.quan_huyen || '').then(() => {
                        if (address.quan_huyen) {
                            const dOpt = Array.from(modalQuan.options).find(o => o.value === address.quan_huyen);
                            if (dOpt) loadWards(dOpt.dataset.code, modalPhuong, address.phuong_xa || '');
                        }
                    });
                }
            }
        });

        if (address) {
            addressForm.querySelector('[name="hoten"]').value = address.hoten || '';
            addressForm.querySelector('[name="sdt"]').value = address.sdt || '';
            addressForm.querySelector('[name="diachi_cuthe"]').value = address.diachi_cuthe || '';
        }

        addressModal.classList.add('show');
    }

    function closeAddressModal() {
        addressModal?.classList.remove('show');
        editingAddressId = null;
    }

    // Close modal buttons
    addressModal?.querySelectorAll('.acc-modal-close, .btn-cancel-modal').forEach(btn => {
        btn.addEventListener('click', closeAddressModal);
    });

    addressModal?.addEventListener('click', (e) => {
        if (e.target === addressModal) closeAddressModal();
    });

    // Add address button
    if (btnAddAddress) {
        btnAddAddress.addEventListener('click', () => openAddressModal());
    }

    // Add address card click
    document.addEventListener('click', (e) => {
        const addCard = e.target.closest('.add-address-card');
        if (addCard) openAddressModal();
    });

    // Save address
    const btnSaveAddr = document.getElementById('btnSaveAddress');
    if (btnSaveAddr) {
        btnSaveAddr.addEventListener('click', () => {
            const fd = new FormData(addressForm);
            fd.append('action', editingAddressId ? 'edit' : 'add');
            if (editingAddressId) fd.append('id', editingAddressId);

            // Basic validation
            if (!fd.get('hoten') || !fd.get('sdt') || !fd.get('diachi_cuthe')) {
                Toast.error('Vui lòng điền đầy đủ thông tin bắt buộc');
                return;
            }

            btnSaveAddr.disabled = true;
            btnSaveAddr.innerHTML = '<span class="acc-spinner"></span>';

            fetch(BASE + 'auth/api_diachi.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Toast.success(data.message);
                        closeAddressModal();
                        loadAddresses();
                    } else {
                        Toast.error(data.message);
                    }
                })
                .catch(() => Toast.error('Lỗi kết nối'))
                .finally(() => {
                    btnSaveAddr.disabled = false;
                    btnSaveAddr.innerHTML = '<i class="fas fa-save"></i> Lưu';
                });
        });
    }

    // Load addresses
    function loadAddresses() {
        if (!addressGrid) return;

        fetch(BASE + 'auth/api_diachi.php')
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                renderAddresses(data.data);
            })
            .catch(() => { });
    }

    function renderAddresses(addresses) {
        if (!addressGrid) return;
        let html = '';

        if (addresses.length === 0) {
            html = `<div class="acc-empty-state">
                <i class="fas fa-map-marker-alt"></i>
                <p>Chưa có địa chỉ nào. Hãy thêm địa chỉ giao hàng!</p>
            </div>`;
        } else {
            addresses.forEach(addr => {
                const parts = [addr.diachi_cuthe, addr.phuong_xa, addr.quan_huyen, addr.tinh].filter(Boolean);
                html += `
                <div class="address-card ${addr.macdinh == 1 ? 'is-default' : ''}" data-id="${addr.id}">
                    <div class="address-card-header">
                        <div>
                            <span class="address-card-name">${escHtml(addr.hoten)}</span>
                            <span class="address-card-phone">${escHtml(addr.sdt)}</span>
                        </div>
                        ${addr.macdinh == 1 ? '<span class="address-badge address-badge-default"><i class="fas fa-check"></i> Mặc định</span>' : ''}
                    </div>
                    <div class="address-card-detail">${escHtml(parts.join(', '))}</div>
                    <div class="address-card-actions">
                        <button class="acc-btn-ghost addr-edit-btn" data-addr='${JSON.stringify(addr)}'><i class="fas fa-pen"></i> Sửa</button>
                        <button class="acc-btn-ghost addr-delete-btn" data-id="${addr.id}"><i class="fas fa-trash"></i> Xóa</button>
                        ${addr.macdinh != 1 ? `<button class="acc-btn-ghost addr-default-btn" data-id="${addr.id}"><i class="fas fa-star"></i> Đặt mặc định</button>` : ''}
                    </div>
                </div>`;
            });
        }

        // Add button card (max 5)
        if (addresses.length < 5) {
            html += `<div class="add-address-card" id="btnAddAddress">
                <i class="fas fa-plus"></i>
                <span>Thêm địa chỉ mới (${addresses.length}/5)</span>
            </div>`;
        }

        addressGrid.innerHTML = html;
    }

    // Delegate address actions
    document.addEventListener('click', (e) => {
        // Edit
        const editBtn = e.target.closest('.addr-edit-btn');
        if (editBtn) {
            const addr = JSON.parse(editBtn.dataset.addr);
            openAddressModal(addr);
            return;
        }

        // Delete
        const deleteBtn = e.target.closest('.addr-delete-btn');
        if (deleteBtn) {
            if (!confirm('Bạn có chắc muốn xóa địa chỉ này?')) return;
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', deleteBtn.dataset.id);
            fetch(BASE + 'auth/api_diachi.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { Toast.success(data.message); loadAddresses(); }
                    else Toast.error(data.message);
                })
                .catch(() => Toast.error('Lỗi kết nối'));
            return;
        }

        // Set default
        const defaultBtn = e.target.closest('.addr-default-btn');
        if (defaultBtn) {
            const fd = new FormData();
            fd.append('action', 'set_default');
            fd.append('id', defaultBtn.dataset.id);
            fetch(BASE + 'auth/api_diachi.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) { Toast.success(data.message); loadAddresses(); }
                    else Toast.error(data.message);
                })
                .catch(() => Toast.error('Lỗi kết nối'));
        }
    });

    // ============================================================
    // SIZE GIÀY YÊU THÍCH
    // ============================================================
    const SIZE_DATA = {
        EU: ['35', '36', '37', '38', '39', '40', '41', '42', '43', '44', '45', '46'],
        US: ['4', '4.5', '5', '5.5', '6', '6.5', '7', '7.5', '8', '8.5', '9', '9.5', '10', '10.5', '11', '11.5', '12', '13'],
        CM: ['22', '22.5', '23', '23.5', '24', '24.5', '25', '25.5', '26', '26.5', '27', '27.5', '28', '28.5', '29']
    };

    let currentSizeSystem = 'EU';
    let savedSizes = [];
    let selectedSize = null;

    const sizeGrid = document.getElementById('sizeGrid');
    const savedSizesList = document.getElementById('savedSizesList');
    const sizeNote = document.getElementById('sizeNote');
    const btnSaveSize = document.getElementById('btnSaveSize');

    // System tabs
    document.querySelectorAll('.size-system-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.size-system-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentSizeSystem = tab.dataset.system;
            selectedSize = null;
            renderSizeGrid();
        });
    });

    function renderSizeGrid() {
        if (!sizeGrid) return;
        const sizes = SIZE_DATA[currentSizeSystem] || [];
        sizeGrid.innerHTML = sizes.map(s => {
            const isSaved = savedSizes.some(sv => sv.he_size === currentSizeSystem && sv.size_value === s);
            const isSelected = selectedSize === s;
            let cls = 'size-chip';
            if (isSaved) cls += ' saved';
            else if (isSelected) cls += ' selected';
            return `<div class="${cls}" data-size="${s}">${s}</div>`;
        }).join('');
    }

    // Size chip click
    document.addEventListener('click', (e) => {
        const chip = e.target.closest('.size-chip');
        if (!chip) return;
        const size = chip.dataset.size;

        // If already saved, deselect
        if (chip.classList.contains('saved')) {
            // Find and confirm delete
            const saved = savedSizes.find(sv => sv.he_size === currentSizeSystem && sv.size_value === size);
            if (saved && confirm(`Bỏ size ${currentSizeSystem} ${size} khỏi yêu thích?`)) {
                deleteSize(saved.id);
            }
            return;
        }

        selectedSize = size;
        renderSizeGrid();
    });

    // Save size
    if (btnSaveSize) {
        btnSaveSize.addEventListener('click', () => {
            if (!selectedSize) {
                Toast.error('Vui lòng chọn 1 size trước');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'save');
            fd.append('he_size', currentSizeSystem);
            fd.append('size_value', selectedSize);
            fd.append('ghichu', sizeNote?.value || '');

            btnSaveSize.disabled = true;

            fetch(BASE + 'auth/api_sizegiay.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Toast.success(data.message);
                        selectedSize = null;
                        loadSizes();
                    } else {
                        Toast.error(data.message);
                    }
                })
                .catch(() => Toast.error('Lỗi kết nối'))
                .finally(() => { btnSaveSize.disabled = false; });
        });
    }

    function deleteSize(id) {
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        fetch(BASE + 'auth/api_sizegiay.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success) { Toast.success(data.message); loadSizes(); }
                else Toast.error(data.message);
            })
            .catch(() => Toast.error('Lỗi kết nối'));
    }

    function loadSizes() {
        fetch(BASE + 'auth/api_sizegiay.php')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    savedSizes = data.data;
                    renderSizeGrid();
                    renderSavedSizes();
                }
            })
            .catch(() => { });
    }

    function renderSavedSizes() {
        if (!savedSizesList) return;
        if (savedSizes.length === 0) {
            savedSizesList.innerHTML = '<div class="acc-empty-state"><i class="fas fa-shoe-prints"></i><p>Chưa có size nào được lưu</p></div>';
            return;
        }

        savedSizesList.innerHTML = savedSizes.map(s => `
            <div class="saved-size-item">
                <div>
                    <span class="saved-size-value">${escHtml(s.size_value)}</span>
                    <span class="saved-size-system">${escHtml(s.he_size)}</span>
                </div>
                <span class="saved-size-note">${s.ghichu ? escHtml(s.ghichu) : ''}</span>
                <button class="saved-size-delete" onclick="event.stopPropagation();" data-id="${s.id}"><i class="fas fa-times"></i></button>
            </div>
        `).join('');
    }

    // Delegate saved size delete
    document.addEventListener('click', (e) => {
        const delBtn = e.target.closest('.saved-size-delete');
        if (delBtn) {
            deleteSize(delBtn.dataset.id);
        }
    });

    // ============================================================
    // UTILITY
    // ============================================================
    function escHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ============================================================
    // INIT
    // ============================================================
    loadAddresses();
    loadSizes();
    Toast.init();

})();
