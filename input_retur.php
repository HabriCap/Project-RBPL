<?php
include "koneksi.php";

$data_nota = mysqli_query($koneksi,"
SELECT DISTINCT n.id_nota, n.nomor_nota, n.tanggal_nota, n.supplier
FROM nota n
JOIN barang b ON b.id_nota = n.id_nota
WHERE LOWER(b.status_barang) = 'cacat'
AND (b.status_retur IS NULL OR b.status_retur!='sudah')
");

if(isset($_POST['submit'])){

    $nomor = $_POST['nomor_retur'];
    $tanggal = $_POST['tanggal_retur'];
    $supplier = $_POST['retur_supplier'];

    $alasan = $_POST['alasan'];
    $jenis = $_POST['jenis_retur'];
    $id_barang_list = $_POST['id_barang'];

    $tanggapan = $_POST['tanggapan'];

    $nama_file_global = "";

    if(isset($_FILES['tindaklanjut']['name']) && $_FILES['tindaklanjut']['name'] != ""){
        $nama_file_global = time()."_".$_FILES['tindaklanjut']['name'];
        move_uploaded_file($_FILES['tindaklanjut']['tmp_name'], "uploads/".$nama_file_global);
    }

    foreach($id_barang_list as $i => $id_brg){

        // 🔥 CEK DUPLIKASI
        $cek = mysqli_query($koneksi,"SELECT * FROM retur WHERE id_barang='$id_brg'");
        if(mysqli_num_rows($cek) > 0){
            continue;
        }

        $ket = $alasan[$i];
        $jns = $jenis[$i];

        $ambil = mysqli_query($koneksi,"SELECT * FROM barang WHERE id_barang='$id_brg'");
        $b = mysqli_fetch_assoc($ambil);

        $qty = $b['jumlah_barang'];
        $nama_foto = $b['foto_bukti'];

        mysqli_query($koneksi,"INSERT INTO retur
        (id_barang, nomor_retur, tanggal_retur, retur_supplier, jenis_retur, jumlah_cacat, alasan, foto_retur, tanggapan, tindaklanjut)
        VALUES
        ('$id_brg','$nomor','$tanggal','$supplier','$jns','$qty','$ket','$nama_foto','$tanggapan','$nama_file_global')");

        mysqli_query($koneksi,"
        UPDATE barang 
        SET status_retur='sudah' 
        WHERE id_barang='$id_brg'
        ");
    }

    header("Location: status_success_input_retur.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Input Retur Barang</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",sans-serif;}
body{background:#efefef;}

.header{background:#3f7aa3;color:white;padding:18px 20px;display:flex;align-items:center;gap:12px;}
.back-btn{width:38px;height:38px;border-radius:50%;background:#48b5c1;display:flex;align-items:center;justify-content:center;}
.header h2{font-size:18px;}

.container{padding:25px 20px;}

.card{
margin-top:25px;
background:white;
padding:20px;
border-radius:22px;
box-shadow:0 15px 30px rgba(0,0,0,0.08);
position:relative;
}

.close-btn{
position:absolute;
top:-15px;
left:20px;
background:white;
border:2px solid red;
border-radius:8px;
width:30px;height:30px;
display:flex;align-items:center;justify-content:center;
color:red;font-weight:bold;
}

.form-group input{
width:100%;height:40px;border:none;border-radius:12px;
background:#e9edf2;padding:0 12px;
}

.barang-title{
color:white;
font-size:13px;
margin-bottom:5px;
}

.barang-input{
width:100%;
border-radius:10px;
border:none;
background:#d7dbe1;
padding:8px;
margin-bottom:6px;
}

.upload-box{
background:#d7dbe1;
height:90px;
border-radius:14px;
display:flex;
align-items:center;
justify-content:center;
font-size:12px;
color:#777;
cursor:pointer;
}

.detail-wrapper{
max-height:0;
overflow:hidden;
transition:0.4s;
}

.detail-wrapper.show{
max-height:2000px;
}

.submit-btn{
width:100%;
height:45px;
border:none;
border-radius:14px;
background:#5e91b2;
color:white;
font-weight:600;
margin-top:12px;
}

.barang-box{
background:#7ea2b9;
padding:15px;
border-radius:16px;
margin-top:15px;
}

.tanggapan-box{
background:#7ea2b9;
padding:15px;
border-radius:16px;
margin-top:20px;
}

.expand-btn{
width:42px;
height:42px;
border-radius:50%;
background:#dfe3e8;
display:flex;
align-items:center;
justify-content:center;
cursor:pointer;
}

.expand-btn.inside{
position:absolute;
left:50%;
transform:translateX(-50%);
bottom:-20px;
}

.expand-btn.outside{
margin:10px auto 0;
display:flex;
}

textarea.barang-input{
resize:none;
height:80px;
overflow-y:auto;
}

textarea.barang-input:focus{
outline:none;
box-shadow:0 0 0 2px rgba(94,145,178,0.3);
}
</style>
</head>

<body>

<div class="header">
<div class="back-btn">←</div>
<h2>Input Retur Barang</h2>
</div>

<div class="container">

<form method="POST" enctype="multipart/form-data">

<?php $no=1; while($n = mysqli_fetch_assoc($data_nota)) { ?>

<div class="card">

<div class="close-btn">✖</div>

<input type="hidden" name="id_nota" value="<?= $n['id_nota'] ?>">

<div class="form-group">
<label>Nomer Nota</label>
<input name="nomor_retur" value="<?= $n['nomor_nota'] ?>" readonly>
</div>

<div class="form-group">
<label>Tanggal Nota</label>
<input type="date" name="tanggal_retur" value="<?= $n['tanggal_nota'] ?>" readonly>
</div>

<!-- 🔥 supplier disembunyikan -->
<div class="form-group" id="supplier-<?= $no ?>" style="display:none;">
<label>Nama Supplier</label>
<input name="retur_supplier" value="<?= $n['supplier'] ?>" readonly>
</div>

<div class="expand-btn inside" id="btn-inside-<?= $no ?>" onclick="toggleDetail('<?= $no ?>')">
▼
</div>

<div class="detail-wrapper" id="detail-<?= $no ?>">

<?php
$data_barang = mysqli_query($koneksi,"
SELECT *
FROM barang
WHERE id_nota = '".$n['id_nota']."'
AND LOWER(status_barang)='cacat'
");

while($b = mysqli_fetch_assoc($data_barang)){
?>

<div class="barang-box">

<input class="barang-input" value="<?= $b['nama_barang'] ?>" readonly>

<div class="barang-title">Jumlah</div>
<input class="barang-input" value="<?= $b['jumlah_barang'] ?>" readonly>

<div class="barang-title">Lampiran Bukti</div>

<div class="upload-box" onclick="openFile('<?= $b['foto_bukti'] ?>')">
<?= !empty($b['foto_bukti']) ? $b['foto_bukti'] : 'Tidak ada file' ?>
</div>

<div class="barang-title">Keterangan / Keluhan</div>
<textarea name="alasan[]" class="barang-input"><?= $b['keterangan'] ?></textarea>

<input type="hidden" name="id_barang[]" value="<?= $b['id_barang'] ?>">
<input type="hidden" name="jenis_retur[]" value="<?= $b['jenis_barang'] ?>">

</div>

<?php } ?>

<div class="tanggapan-box">
<div class="barang-title">Tanggapan & Tindak Lanjut Supplier</div>
<textarea name="tanggapan" class="barang-input"></textarea>

<div class="barang-title">Lampiran Bukti Tindak Lanjut (Opt.)</div>
<div class="upload-box" onclick="this.querySelector('input').click()">
Klik upload dokumen
<input type="file" name="tindaklanjut" hidden>
</div>
</div>

<button class="submit-btn" name="submit">
Simpan Data Retur Barang
</button>

</div>

</div>

<div class="expand-btn outside" id="btn-outside-<?= $no ?>" onclick="toggleDetail('<?= $no ?>')" style="display:none;">
▲
</div>

<?php $no++; } ?>

</form>

</div>

<script>
function toggleDetail(id){
    let detail = document.getElementById("detail-"+id);
    let insideBtn = document.getElementById("btn-inside-"+id);
    let outsideBtn = document.getElementById("btn-outside-"+id);
    let supplier = document.getElementById("supplier-"+id);

    detail.classList.toggle("show");

    if(detail.classList.contains("show")){
        insideBtn.style.display = "none";
        outsideBtn.style.display = "flex";
        if(supplier) supplier.style.display = "block";
    } else {
        insideBtn.style.display = "flex";
        outsideBtn.style.display = "none";
        if(supplier) supplier.style.display = "none";
    }
}

function openFile(file){
    if(file){
        window.open("uploads/"+file,"_blank");
    }
}
</script>

</body>
</html>