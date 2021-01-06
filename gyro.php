<?php

$videoFile = 'video.mp4';

echo 'Extracting gyro data from video file'.PHP_EOL;

if(!file_exists($videoFile)){
  die('Video not found');
}

// Extract gyro data
exec('./exiftool/exiftool -api largefilesupport=1 -ee '.$videoFile.' > raw_data.txt');


echo 'Generating CSV file'.PHP_EOL;

$data = file_get_contents('raw_data.txt');
$data = explode(PHP_EOL, $data);

// Extract only required lines from the metadata
$data = array_values(array_filter($data, function($row){
    $row = explode(':', $row);
    $key = trim($row[0]);
    return in_array($key, [
        'Time Code',
        'Angular Velocity',
        //'Accelerometer'
    ]);
}));


$array = [];
$array_line = [];
foreach($data as $row){

    $items = explode(':', $row);

    $key = trim($items[0]);
    $value = trim($items[1]);

    if(in_array($key, ['Angular Velocity','Accelerometer'])){
        $key = str_replace('Angular Velocity', 'gyroADC', $key);
        $value = explode(' ', $value);
        $array_line[ $key .'[0]' ] = round($value[2] * 100 / pi());
        $array_line[ $key .'[1]' ] = round($value[0] * 100 / pi());
        $array_line[ $key .'[2]' ] = round($value[1] * 100 / pi());
    } else {
        $array_line[ 'time' ] = $value;
    }

    if ( count( $array_line ) === 4 ) {
        $array[]    = $array_line;
        $array_line = [];
    }
}


$class = new Array2Csv();

$class->data($array)->save('gyro_data.csv');

echo 'Done!';

class Array2Csv {

    public $title = 'Export';

    public $headings;

    public $data = [];

    public $seperator = ',';

    public function __construct() {
    }

    /**
     * Set the file title/name
     *
     * @param string $value
     */
    public function title(string $value){
        $this->title = $value;
    }

    /**
     * Create headings
     *
     * @param array $data
     *
     * @return $this
     */
    public function headings(array $data)
    {
        $this->headings = $data;
        return $this;
    }

    /**
     * Add the data
     *
     * @param array $data
     *
     * @return $this
     */
    public function data(array $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Column seperator
     *
     * @param string $value
     *
     * @return $this
     */
    public function seperator(string $value)
    {
        $this->seperator = $value;
        return $this;
    }

    /**
     *  Generate the CSV
     */
    public function convert()
    {
        $data = [];
        // Add titles
        $data[] = $this->headings ?? array_keys($this->data[0]);
        // Add the data and remove the keys
        $data = array_merge($data, array_map(function($row){
            return array_values($row);
        }, $this->data));

        $text = '';

        foreach($data as $row){
            $row = array_map(function($column){
                return '"' . $column . '"';
            }, $row);
            $text .= implode($this->seperator, $row) . PHP_EOL;
        }

        return $text;
    }

    /**
     *  Generate the CSV and download it
     */
    public function download()
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' .$this->title.'.csv"');

        echo $this->convert();
        die;
    }

    /**
     *  Generate the CSV and save it
     */
    public function save(string $name)
    {
        $data = $this->convert();
        file_put_contents($name, $data);
    }

}
