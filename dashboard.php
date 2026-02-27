<?php
require 'auth.php';
require 'config/config.php'; // เชื่อมต่อ Oracle

// 1. ดึงยอดรวมเฉพาะคนที่เข้าร่วมงาน (IS_ATTENDED = 'Y')
$sql_total = "SELECT 
    COUNT(*) as TOTAL_ATTENDED,
    SUM(CASE WHEN STATUS = 'DONE' THEN 1 ELSE 0 END) as CHECKED_IN,
    SUM(CASE WHEN STATUS != 'DONE' OR STATUS IS NULL THEN 1 ELSE 0 END) as PENDING
    FROM EMP_CHECKIN
    WHERE IS_ATTENDED = 'Y'";
$stid_total = oci_parse($conn, $sql_total);
oci_execute($stid_total);
$total = oci_fetch_array($stid_total, OCI_ASSOC);

$attended_count = $total['TOTAL_ATTENDED'] ?? 0;
$in_count = $total['CHECKED_IN'] ?? 0;
$pending_count = $total['PENDING'] ?? 0;

// 2. ดึงข้อมูลแยกตาม Plant (เฉพาะ IS_ATTENDED = 'Y')
$sql_plant = "SELECT PLANT, 
              COUNT(*) as TOTAL,
              SUM(CASE WHEN STATUS = 'DONE' THEN 1 ELSE 0 END) as CHECKED_IN,
              SUM(CASE WHEN STATUS != 'DONE' OR STATUS IS NULL THEN 1 ELSE 0 END) as PENDING
              FROM EMP_CHECKIN 
              WHERE IS_ATTENDED = 'Y'
              GROUP BY PLANT 
              ORDER BY PLANT";
$stid_plant = oci_parse($conn, $sql_plant);
oci_execute($stid_plant);
$plant_data = [];
while ($row = oci_fetch_array($stid_plant, OCI_ASSOC)) {
    $plant_data[] = $row;
}

// 3. รายชื่อ 5 คนล่าสุดที่แสกนเข้างาน (เฉพาะ IS_ATTENDED = 'Y')
$sql_recent = "SELECT * FROM (
    SELECT EMP_NAME, PLANT, TO_CHAR(SCAN_TIME, 'HH24:MI:SS') as TIME 
    FROM EMP_CHECKIN 
    WHERE STATUS = 'DONE' AND IS_ATTENDED = 'Y'
    ORDER BY SCAN_TIME DESC
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
                    <i class="fas fa-users fa-3x mb-2 gold-text"></i>
                    <h6 class="opacity-75 text-uppercase small mt-2">พนักงานที่เข้าร่วมงาน</h6>
                    <h1 class="fw-bold"><?php echo number_format($attended_count); ?></h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center" style="background: rgba(16, 185, 129, 0.1);">
                    <i class="fas fa-user-check fa-3x mb-2 text-success"></i>
                    <h6 class="opacity-75 text-uppercase small mt-2">เช็คอินเข้างานแล้ว</h6>
                    <h1 class="fw-bold text-success"><?php echo number_format($in_count); ?></h1>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center" style="background: rgba(249, 115, 22, 0.1);">
                    <i class="fas fa-hourglass-half fa-3x mb-2" style="color: #f97316;"></i>
                    <h6 class="opacity-75 text-uppercase small mt-2">รอเช็คอิน</h6>
                    <h1 class="fw-bold" style="color: #f97316;"><?php echo number_format($pending_count); ?></h1>
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

        // กราฟ Highcharts - เฉพาะคนที่ IS_ATTENDED = 'Y'
        Highcharts.chart('plantChart', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent',
                style: {
                    fontFamily: 'Sarabun, sans-serif'
                }
            },
            title: {
                text: 'สถิติผู้เข้าร่วมงานแยกตาม Plant',
                style: {
                    color: '#ffd700',
                    fontWeight: 'bold',
                    fontSize: '22px',
                    textShadow: '0 0 20px #ffd700, 0 0 30px #f1c40f'
                }
            },
            subtitle: {
                text: 'SAB 40th Anniversary Celebration',
                style: {
                    color: '#fbbf24',
                    fontSize: '14px',
                    textShadow: '0 0 10px #fbbf24'
                }
            },
            xAxis: {
                categories: [<?php foreach ($plant_data as $p) echo "'" . $p['PLANT'] . "',"; ?>],
                labels: {
                    style: {
                        color: '#fff',
                        fontSize: '14px',
                        fontWeight: 'bold'
                    }
                },
                lineColor: '#ffd700',
                lineWidth: 2,
                tickColor: '#ffd700'
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'จำนวนพนักงาน (คน)',
                    style: {
                        color: '#ffd700',
                        fontSize: '14px',
                        fontWeight: 'bold'
                    }
                },
                labels: {
                    style: {
                        color: '#fef3c7'
                    }
                },
                gridLineColor: 'rgba(255, 215, 0, 0.15)',
                gridLineWidth: 1
            },
            legend: {
                align: 'center',
                verticalAlign: 'bottom',
                layout: 'horizontal',
                itemStyle: {
                    color: '#fff',
                    fontSize: '14px',
                    fontWeight: '600'
                },
                itemHoverStyle: {
                    color: '#ffd700',
                    textShadow: '0 0 10px #ffd700'
                },
                itemMarginBottom: 10
            },
            tooltip: {
                shared: true,
                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                borderColor: '#ffd700',
                borderWidth: 2,
                borderRadius: 15,
                shadow: {
                    color: 'rgba(255, 215, 0, 0.5)',
                    offsetX: 0,
                    offsetY: 0,
                    width: 15
                },
                style: {
                    color: '#fff',
                    fontSize: '14px'
                },
                headerFormat: '<div style="font-size: 18px; font-weight: bold; color: #ffd700; text-shadow: 0 0 10px #ffd700; margin-bottom: 8px;">{point.key}</div>',
                pointFormat: '<div style="padding: 4px 0;"><span style="color:{series.color}; text-shadow: 0 0 8px {series.color};">⬤</span> {series.name}: <b style="color: #fff; font-size: 16px;">{point.y}</b> คน</div>',
                footerFormat: '',
                useHTML: true
            },
            plotOptions: {
                column: {
                    borderRadius: 6,
                    pointPadding: 0.1,
                    groupPadding: 0.15,
                    borderWidth: 2,
                    dataLabels: {
                        enabled: true,
                        color: '#fff',
                        style: {
                            fontSize: '13px',
                            fontWeight: 'bold',
                            textOutline: 'none'
                        },
                        formatter: function() {
                            return this.y > 0 ? this.y + ' คน' : '';
                        }
                    },
                    states: {
                        hover: {
                            brightness: 0.2
                        }
                    }
                },
                series: {
                    animation: {
                        duration: 1500,
                        easing: 'easeOutQuart'
                    }
                }
            },
            series: [{
                name: '🟠 รอเช็คอิน',
                data: [<?php foreach ($plant_data as $p) echo $p['PENDING'] . ","; ?>],
                color: {
                    linearGradient: {
                        x1: 0,
                        y1: 0,
                        x2: 0,
                        y2: 1
                    },
                    stops: [
                        [0, '#fb923c'],
                        [0.5, '#f97316'],
                        [1, '#ea580c']
                    ]
                },
                borderColor: '#fb923c',
                shadow: {
                    color: 'rgba(249, 115, 22, 0.6)',
                    offsetX: 0,
                    offsetY: 0,
                    width: 12
                }
            }, {
                name: '🟢 เช็คอินแล้ว',
                data: [<?php foreach ($plant_data as $p) echo $p['CHECKED_IN'] . ","; ?>],
                color: {
                    linearGradient: {
                        x1: 0,
                        y1: 0,
                        x2: 0,
                        y2: 1
                    },
                    stops: [
                        [0, '#34d399'],
                        [0.5, '#10b981'],
                        [1, '#059669']
                    ]
                },
                borderColor: '#34d399',
                shadow: {
                    color: 'rgba(16, 185, 129, 0.6)',
                    offsetX: 0,
                    offsetY: 0,
                    width: 12
                }
            }],
            credits: {
                enabled: false
            },
            responsive: {
                rules: [{
                    condition: {
                        maxWidth: 500
                    },
                    chartOptions: {
                        legend: {
                            layout: 'horizontal',
                            align: 'center',
                            verticalAlign: 'bottom'
                        }
                    }
                }]
            }
        });
    </script>

</body>

</html>