<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>2FA | Lấy mã bảo mật 2 lớp</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <!-- SweetAlert2 CSS + JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@10/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>

</head>
<body>
<div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom shadow-sm">
    <h5 class="my-0 mr-md-auto font-weight-normal tttttt">2FA</h5>
    <nav class="my-2 my-md-0 mr-md-3">
    </nav>
</div>

<div class="container mt-5">
    <form id="otpForm">
        <div class="form-group">
            <label for="secretKey">Secret Key</label>
            <textarea class="form-control" id="secretKey" name="key" rows="4" placeholder="5NQV HJNP QCNP JJIY ... 
L2WA M4BZ RBM2 YVJP ..."></textarea>
            <small class="form-text text-muted">Chỉ chấp nhận chữ cái, số và khoảng cách. Mỗi dòng sẽ lấy 1 code.</small>
        </div>
        <button type="submit" class="btn btn-primary">LẤY CODE</button>
    </form>

    <div class="mt-4">
        <h5>Kết quả:</h5>
        <div class="mb-2 text-muted" id="countdownTimer" style="display: none;">Đang cập nhật mã...</div>
        <table class="table table-bordered table-striped" id="resultTable" style="display: none;">
            <thead class="thead-dark">
                <tr>
                    <th scope="col text-center" style="width:120px; text-align:center">CODE</th>
                    <th scope="col text-center">Secret Key</th>
                </tr>
            </thead>
            <tbody id="resultBody"></tbody>
        </table>
    </div>
</div>

<!-- jQuery + Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script>
let intervalId;
let countdownInterval;

const fetchCodes = async () => {
    const textarea = document.getElementById('secretKey').value.trim();
    const lines = textarea.split('\n');
    const resultBody = document.getElementById('resultBody');
    const countdownTimer = document.getElementById('countdownTimer');
    resultBody.innerHTML = '';
    countdownTimer.style.display = 'block';

    const rowRefs = [];

    for (let line of lines) {
        const key = line.trim().replace(/\s+/g, '');
        if (key) {
            const row = document.createElement('tr');

            const codeCell = document.createElement('td');
            const codeInput = document.createElement('input');
            codeInput.style.textAlign = 'center';
            codeInput.type = 'text';
            codeInput.className = 'form-control font-weight-bold bg-dark text-light';
            codeInput.readOnly = true;

            // Chức năng sao chép
            codeInput.addEventListener('click', () => {
                const value = codeInput.value;

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(value).then(() => {
                        Swal.fire({
                            title: "Đã sao chép!",
                            text: `Mã "${value}" đã được sao chép.`,
                            icon: "success"
                        });
                    }).catch(() => {
                        Swal.fire("Lỗi!", "Không thể sao chép mã.", "error");
                    });
                } else {
                    const tempInput = document.createElement('textarea');
                    tempInput.value = value;
                    document.body.appendChild(tempInput);
                    tempInput.select();
                    try {
                        document.execCommand('copy');
                        Swal.fire({
                            title: "Đã sao chép!",
                            text: `Mã "${value}" đã được sao chép.`,
                            icon: "success"
                        });
                    } catch (err) {
                        Swal.fire("Lỗi!", "Không thể sao chép mã.", "error");
                    }
                    document.body.removeChild(tempInput);
                }
            });

            codeCell.appendChild(codeInput);

            const keyCell = document.createElement('td');
            const keyInput = document.createElement('input');
            keyInput.type = 'text';
            keyInput.className = 'form-control';
            keyInput.value = line.trim();
            keyInput.readOnly = true;
            keyCell.appendChild(keyInput);

            row.appendChild(codeCell);
            row.appendChild(keyCell);
            resultBody.appendChild(row);

            rowRefs.push({ key, codeInput });
        }
    }

    const updateCodes = async () => {
        for (let item of rowRefs) {
            try {
                const res = await fetch(`2fa.php?key=${encodeURIComponent(item.key)}`);
                const code = await res.text();
                item.codeInput.value = code;
            } catch (err) {
                item.codeInput.value = 'Lỗi';
            }
        }
    };

    await updateCodes();

    clearInterval(intervalId);
    clearInterval(countdownInterval);

    let remaining = 30;
    countdownTimer.innerText = `Mã sẽ cập nhật sau ${remaining}s`;

    countdownInterval = setInterval(() => {
        remaining--;
        if (remaining > 0) {
            countdownTimer.innerText = `Mã sẽ cập nhật sau ${remaining}s`;
        } else {
            remaining = 30;
            updateCodes();
            countdownTimer.innerText = `Mã sẽ cập nhật sau ${remaining}s`;
        }
    }, 1000);

    intervalId = setInterval(updateCodes, 30000);
};

document.getElementById('otpForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const textarea = document.getElementById('secretKey').value.trim();

    if (!textarea) {
        Swal.fire("Lỗi!", "Bạn chưa nhập SECRET KEY", "error");
        return;
    }

    document.getElementById('resultTable').style.display = 'table';
    fetchCodes();
});
</script>
</body>
</html>
