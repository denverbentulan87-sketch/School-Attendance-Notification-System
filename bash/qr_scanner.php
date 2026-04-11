<!DOCTYPE html>
<html>
<head>
<script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>

<h2>Scan QR for Attendance</h2>

<div id="reader" style="width:300px;"></div>

<script>
function onScanSuccess(decodedText) {
    fetch("mark_attendance.php?id=" + decodedText)
    .then(res => alert("Attendance Marked"));
}

new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 })
    .render(onScanSuccess);
</script>

</body>
</html>