<?php
// Function to get the server load
function getServerLoad() {
    // Get the system load averages for 1, 5, and 15 minutes
    $load = sys_getloadavg();  // This gives the system load over 1, 5, and 15 minutes

    // Get the number of CPU cores (for accurate percentage calculation)
    $cpuCores = shell_exec("nproc"); // On Linux, this returns the number of CPU cores

    // Calculate the load as a percentage of CPU capacity
    $loadPercent = array_map(function($l) use ($cpuCores) {
        return min(100, round(($l / $cpuCores) * 100));  // Cap the load at 100%
    }, $load);

    // Get memory usage (in MB)
    $memory = shell_exec('free -m');  // This gives memory stats in MB
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $memory, $matches);
    $totalMemory = $matches[1];  // Total memory in MB
    $usedMemory = $matches[2];   // Used memory in MB
    $freeMemory = $matches[3];   // Free memory in MB

    // Convert memory usage to GB
    $memoryUsageGB = round($usedMemory / 1024, 2);  // Convert used memory to GB

    // Get CPU Usage (this works on Linux systems)
    $cpu = shell_exec("top -bn1 | grep 'Cpu(s)'");  // Get current CPU usage
    preg_match("/(\d+\.\d+)\s*id/", $cpu, $matches);
    $cpuUsage = 100 - $matches[1];  // CPU usage is the total CPU minus idle

    // Return the load, memory, and CPU usage
    return [
        'load' => $loadPercent,
        'memory' => $memoryUsageGB,
        'cpu' => $cpuUsage
    ];
}

// Output the server load in JSON format
header('Content-Type: application/json');
echo json_encode(getServerLoad());
