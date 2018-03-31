# Usage

```
bin/console replace <directory> '/(.+)\.mp4$/' '$1-2.mp4'
```

This copies:

- all exif data from X.mp4 to X-2.mp4 in <directory>
- creation date of X-2.mp4 from X.mp4
