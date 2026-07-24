<?php
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

echo "<h3>Checking transactions table structure:</h3>";
$result = mysqli_query($conn, "DESCRIBE transactions");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Also show sample data if any
echo "<h3>Sample data in transactions table:</h3>";
$sample = mysqli_query($conn, "SELECT * FROM transactions LIMIT 5");
if(mysqli_num_rows($sample) > 0) {
    echo "<table border='1'>";
    $first = true;
    while($row = mysqli_fetch_assoc($sample)) {
        if($first) {
            echo "<tr>";
            foreach(array_keys($row) as $key) {
                echo "<th>$key</th>";
            }
            echo "</tr>";
            $first = false;
        }
        echo "<tr>";
        foreach($row as $value) {
            echo "<td>$value</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No data found in transactions table.";
}
?>