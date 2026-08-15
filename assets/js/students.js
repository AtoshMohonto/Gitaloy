(function () {
    function fetchOptions(url, select, placeholder) {
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (rows) {
                select.innerHTML = '';
                var opt = document.createElement('option');
                opt.value = '';
                opt.textContent = placeholder;
                select.appendChild(opt);
                rows.forEach(function (row) {
                    var o = document.createElement('option');
                    o.value = row.id;
                    o.textContent = row.name;
                    select.appendChild(o);
                });
            });
    }

    var divisionSel = document.getElementById('f-division');
    var districtSel = document.getElementById('f-district');
    var upazilaSel = document.getElementById('f-upazila');
    var villageSel = document.getElementById('f-village');
    var centerSel = document.getElementById('f-center');
    var base = document.getElementById('api-base') ? document.getElementById('api-base').value : '';

    function loadDistricts(keepValue) {
        var url = base + '/modules/students/api.php?action=districts';
        if (divisionSel.value) { url += '&division_id=' + encodeURIComponent(divisionSel.value); }
        fetchOptions(url, districtSel, 'All districts');
        upazilaSel.innerHTML = '<option value="">All upazilas</option>';
        villageSel.innerHTML = '<option value="">All villages</option>';
        centerSel.innerHTML = '<option value="">All centers</option>';
    }
    function loadUpazilas() {
        var url = base + '/modules/students/api.php?action=upazilas';
        if (districtSel.value) { url += '&district_id=' + encodeURIComponent(districtSel.value); }
        fetchOptions(url, upazilaSel, 'All upazilas');
        villageSel.innerHTML = '<option value="">All villages</option>';
        centerSel.innerHTML = '<option value="">All centers</option>';
    }
    function loadVillages() {
        var url = base + '/modules/students/api.php?action=villages';
        if (upazilaSel.value) { url += '&upazila_id=' + encodeURIComponent(upazilaSel.value); }
        fetchOptions(url, villageSel, 'All villages');
        centerSel.innerHTML = '<option value="">All centers</option>';
    }
    function loadCenters() {
        var url = base + '/modules/students/api.php?action=centers';
        if (villageSel.value) { url += '&village_id=' + encodeURIComponent(villageSel.value); }
        fetchOptions(url, centerSel, 'All centers');
    }

    if (divisionSel) { divisionSel.addEventListener('change', loadDistricts); }
    if (districtSel) { districtSel.addEventListener('change', loadUpazilas); }
    if (upazilaSel) { upazilaSel.addEventListener('change', loadVillages); }
    if (villageSel) { villageSel.addEventListener('change', loadCenters); }

    var searchInput = document.getElementById('student-search');
    var tableBody = document.getElementById('students-table-body');
    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function () {
            var q = searchInput.value.toLowerCase();
            Array.prototype.forEach.call(tableBody.rows, function (row) {
                row.style.display = row.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
})();

(function () {
    var base = document.getElementById('api-base') ? document.getElementById('api-base').value : '';

    function apiPost(formData) {
        return fetch(base + '/modules/students/api.php', { method: 'POST', body: formData })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    return { ok: r.ok, data: data };
                });
            });
    }

    function addOption(select, value, text, selectIt) {
        var o = document.createElement('option');
        o.value = value;
        o.textContent = text;
        select.appendChild(o);
        if (selectIt) { select.value = String(value); }
    }

    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    document.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { closeModal(btn.getAttribute('data-close')); });
    });

    var addVillageBtn = document.getElementById('btn-add-village');
    var villageModalForm = document.getElementById('form-add-village');
    var villageName = document.getElementById('village-name');
    var villageUpazila = document.getElementById('village-upazila');
    var villageMsg = document.getElementById('village-modal-msg');

    if (addVillageBtn && villageModalForm) {
        addVillageBtn.addEventListener('click', function () {
            villageMsg.classList.add('hidden');
            fetch(base + '/modules/students/api.php?action=upazilas')
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    if (rows && rows.forEach && rows.length) {
                        villageUpazila.innerHTML = '<option value="">Select upazila</option>';
                        rows.forEach(function (row) { addOption(villageUpazila, row.id, row.name, false); });
                        var def = villageModalForm.getAttribute('data-default-upazila');
                        if (def) { villageUpazila.value = def; }
                        openModal('modal-village');
                        villageName.focus();
                    } else {
                        villageMsg.textContent = 'No upazilas found yet. An admin must add upazilas under Geography setup first.';
                        villageMsg.classList.remove('hidden');
                        openModal('modal-village');
                    }
                });
        });

        villageModalForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var name = villageName.value.trim();
            if (!name || !villageUpazila.value) {
                villageMsg.textContent = 'Village name and upazila are required.';
                villageMsg.classList.remove('hidden');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'create_village');
            fd.append('name', name);
            fd.append('upazila_id', villageUpazila.value);
            fd.append('csrf_token', villageModalForm.querySelector('input[name="csrf_token"]').value);
            apiPost(fd).then(function (res) {
                if (!res.ok) {
                    villageMsg.textContent = res.data.error || 'Could not create the village.';
                    villageMsg.classList.remove('hidden');
                    return;
                }
                addOption(document.getElementById('form-village'), res.data.id, res.data.name, true);
                closeModal('modal-village');
                villageModalForm.reset();
                if (window.showToast) { window.showToast('Village "' + res.data.name + '" added.', 'success'); }
            });
        });
    }

    var addCenterBtn = document.getElementById('btn-add-center');
    var centerModalForm = document.getElementById('form-add-center');
    var centerName = document.getElementById('center-name');
    var centerVillage = document.getElementById('center-village');
    var centerDescription = document.getElementById('center-description');
    var centerMsg = document.getElementById('center-modal-msg');

    var addClassBtn = document.getElementById('btn-add-class');
    var classModalForm = document.getElementById('form-add-class');
    var className = document.getElementById('class-name');
    var classAgeMin = document.getElementById('class-age-min');
    var classAgeMax = document.getElementById('class-age-max');
    var classMsg = document.getElementById('class-modal-msg');

    if (addClassBtn && classModalForm) {
        addClassBtn.addEventListener('click', function () {
            classMsg.classList.add('hidden');
            classModalForm.reset();
            openModal('modal-class');
            className.focus();
        });

        classModalForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var name = className.value.trim();
            if (!name) {
                classMsg.textContent = 'Class name is required.';
                classMsg.classList.remove('hidden');
                return;
            }
            var min = classAgeMin.value;
            var max = classAgeMax.value;
            if (min && max && parseInt(min, 10) > parseInt(max, 10)) {
                classMsg.textContent = '"Age from" cannot be greater than "Age to".';
                classMsg.classList.remove('hidden');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'create_class');
            fd.append('name', name);
            if (min) { fd.append('age_min', min); }
            if (max) { fd.append('age_max', max); }
            fd.append('csrf_token', classModalForm.querySelector('input[name="csrf_token"]').value);
            apiPost(fd).then(function (res) {
                if (!res.ok) {
                    classMsg.textContent = res.data.error || 'Could not create the class.';
                    classMsg.classList.remove('hidden');
                    return;
                }
                var label = res.data.name;
                if (min || max) {
                    label += ' (' + (min || '?') + '–' + (max || '?') + ' yrs)';
                }
                addOption(document.getElementById('form-class'), res.data.id, label, true);
                closeModal('modal-class');
                classModalForm.reset();
                if (window.showToast) { window.showToast('Class "' + res.data.name + '" added.', 'success'); }
            });
        });
    }

    if (addCenterBtn && centerModalForm) {
        addCenterBtn.addEventListener('click', function () {
            centerMsg.classList.add('hidden');
            fetch(base + '/modules/students/api.php?action=villages')
                .then(function (r) { return r.json(); })
                .then(function (rows) {
                    centerVillage.innerHTML = '<option value="">Select village</option>';
                    if (rows && rows.forEach) {
                        rows.forEach(function (row) { addOption(centerVillage, row.id, row.name, false); });
                    }
                    var mainVillage = document.getElementById('form-village');
                    if (mainVillage && mainVillage.value) { centerVillage.value = mainVillage.value; }
                    openModal('modal-center');
                    centerName.focus();
                });
        });

        centerModalForm.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var name = centerName.value.trim();
            if (!name) {
                centerMsg.textContent = 'Center name is required.';
                centerMsg.classList.remove('hidden');
                return;
            }
            var fd = new FormData();
            fd.append('action', 'create_center');
            fd.append('name', name);
            if (centerVillage.value) { fd.append('village_id', centerVillage.value); }
            if (centerDescription && centerDescription.value.trim()) { fd.append('description', centerDescription.value.trim()); }
            fd.append('csrf_token', centerModalForm.querySelector('input[name="csrf_token"]').value);
            apiPost(fd).then(function (res) {
                if (!res.ok) {
                    centerMsg.textContent = res.data.error || 'Could not create the center.';
                    centerMsg.classList.remove('hidden');
                    return;
                }
                var select = document.getElementById('form-center');
                if (select && select.getAttribute('data-teacher') === '1') {
                    select.innerHTML = '<option value="">Select center</option>';
                    addOption(select, res.data.id, res.data.name, true);
                    if (window.showToast) { window.showToast('Center "' + res.data.name + '" added. It is now your assigned center.', 'success'); }
                } else {
                    addOption(select, res.data.id, res.data.name, true);
                    if (window.showToast) { window.showToast('Center "' + res.data.name + '" added.', 'success'); }
                }
                closeModal('modal-center');
                centerModalForm.reset();
            });
        });
    }
})();
