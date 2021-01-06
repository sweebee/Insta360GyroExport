# Insta360GyroExport

This is just a quick and dirty script to extract gyro data from the insta360 video files. It generates a blackbox CSV file.

Place the video file in the root folder and call it ```video.mp4```. Run ```php gyro.php``` and it should generate a CSV file with the gyro data.


## Gyro order

blackbox has this order for the gyro data:
```
roll > pitch > yaw
```

Insta360 order:
```
pitch > yaw > roll
```

This is already sorted in the script.