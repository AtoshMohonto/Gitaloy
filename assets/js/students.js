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
