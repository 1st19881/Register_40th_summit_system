<?php
require 'config/config.php'; // เชื่อมต่อ Oracle

// 1. ดึงยอดรวมทั้งหมด (ลงทะเบียนแล้ว vs เช็คอินแล้ว)
$sql_total = "SELECT 
    COUNT(*) as REGISTERED,
    SUM(CASE WHEN STATUS = 'DONE' THEN 1 ELSE 0 END) as CHECKED_IN
    FROM EMP_CHECKIN";
$stid_total = oci_parse($conn, $sql_total);
oci_execute($stid_total);
$total = oci_fetch_array($stid_total, OCI_ASSOC);

$reg_count = $total['REGISTERED'] ?? 0;
$in_count = $total['CHECKED_IN'] ?? 0;
$pending_count = $reg_count - $in_count;

// 2. ดึงข้อมูลแยกตาม Plant
$sql_plant = "SELECT PLANT, COUNT(*) as TOTAL, 
              SUM(CASE WHEN STATUS = 'DONE' THEN 1 ELSE 0 END) as DONE
              FROM EMP_CHECKIN GROUP BY PLANT";
$stid_plant = oci_parse($conn, $sql_plant);
oci_execute($stid_plant);
$plant_data = [];
while ($row = oci_fetch_array($stid_plant, OCI_ASSOC)) {
    $plant_data[] = $row;
}

// 3. รายชื่อ 5 คนล่าสุดที่แสกนเข้างาน
$sql_recent = "SELECT * FROM (
    SELECT EMP_NAME, PLANT, TO_CHAR(SCAN_TIME, 'HH24:MI:SS') as TIME 
    FROM EMP_CHECKIN WHERE STATUS = 'DONE' ORDER BY SCAN_TIME DESC
) WHERE ROWNUM <= 5";
$stid_recent = oci_parse($conn, $sql_recent);
oci_execute($stid_recent);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>SAB 40th Dashboard | Real-time</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <style>
        body {
            background: #0f172a;
            color: white;
            font-family: 'Sarabun', sans-serif;
        }

        .dashboard-container {
            padding: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 20px;
            padding: 25px;
            transition: 0.3s;
        }

        .stat-card:hover {
            border-color: #f1c40f;
            transform: translateY(-5px);
        }

        .gold-text {
            color: #f1c40f;
        }

        .chart-box {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 20px;
            height: 400px;
        }

        .recent-list {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 20px;
        }

        .table {
            color: white;
        }
    </style>
    <meta http-equiv="refresh" content="30">
</head>

<body>

    <div class="dashboard-container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold gold-text mb-0">SUMMIT AUTO BODY</h1>
                <p class="text-uppercase tracking-widest opacity-75">40th Anniversary Celebration Dashboard</p>
            </div>
            <div class="text-end">
                <h4 id="clock" class="mb-0"></h4>
                <span class="badge bg-success">LIVE UPDATE</span>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-users fa-3x mb-3 gold-text"></i>
                    <h6 class="text-muted text-uppercase">ลงทะเบียนแล้ว</h6>
                    <h1 class="fw-bold"><?php echo number_format($reg_count); ?></h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center" style="background: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-qrcode fa-3x mb-3 text-success"></i>
                    <h6 class="text-muted text-uppercase">เช็คอินเข้างาน</h6>
                    <h1 class="fw-bold text-success"><?php echo number_format($in_count); ?></h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-clock fa-3x mb-3 text-warning"></i>
                    <h6 class="text-muted text-uppercase">รอดำเนินการ</h6>
                    <h1 class="fw-bold text-warning"><?php echo number_format($pending_count); ?></h1>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-8">
                <div id="plantChart" class="chart-box shadow"></div>
            </div>

            <div class="col-md-4">
                <div class="recent-list shadow h-100">
                    <h5 class="fw-bold gold-text mb-4"><i class="fas fa-history me-2"></i>ผู้เช็คอินล่าสุด</h5>
                    <table class="table table-borderless">
                        <thead>
                            <tr class="text-muted small">
                                <th>ชื่อ</th>
                                <th>Plant</th>
                                <th>เวลา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($recent = oci_fetch_array($stid_recent, OCI_ASSOC)): ?>
                                <tr class="border-bottom border-secondary">
                                    <td><?php echo $recent['EMP_NAME']; ?></td>
                                    <td><span class="badge bg-primary"><?php echo $recent['PLANT']; ?></span></td>
                                    <td class="text-info"><?php echo $recent['TIME']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // แสดงเวลาปัจจุบัน
        setInterval(() => {
            document.getElementById('clock').innerText = new Date().toLocaleTimeString();
        }, 1000);

        // กราฟ Highcharts
        Highcharts.chart('plantChart', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Attendance by Plant',
                style: {
                    color: '#f1c40f',
                    fontWeight: 'bold'
                }
            },
            xAxis: {
                categories: [<?php foreach ($plant_data as $p) echo "'" . $p['PLANT'] . "',"; ?>],
                labels: {
                    style: {
                        color: '#fff'
                    }
                }
            },
            yAxis: {
                title: {
                    text: 'จำนวนคน',
                    style: {
                        color: '#fff'
                    }
                },
                labels: {
                    style: {
                        color: '#fff'
                    }
                },
                gridLineColor: 'rgba(255,255,255,0.1)'
            },
            legend: {
                itemStyle: {
                    color: '#fff'
                }
            },
            series: [{
                name: 'ลงทะเบียน',
                data: [<?php foreach ($plant_data as $p) echo $p['TOTAL'] . ","; ?>],
                color: '#334155'
            }, {
                name: 'แสกนเข้างานแล้ว',
                data: [<?php foreach ($plant_data as $p) echo $p['DONE'] . ","; ?>],
                color: '#f1c40f'
            }],
            plotOptions: {
                column: {
                    borderRadius: 5
                }
            }
        });
    </script>

</body>

</html>