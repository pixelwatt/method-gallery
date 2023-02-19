var gulp = require('gulp'),
sass = require('gulp-dart-sass'),
autoprefixer = require('gulp-autoprefixer'),
rename = require('gulp-rename'),
concat = require('gulp-concat'),
notify = require('gulp-notify'),
browserSync = require('browser-sync').create(),
cleanCSS = require('gulp-clean-css');

gulp.task('styles', function() {
    return gulp.src('./assets/css/method-gallery.scss')
      .pipe(sass({outputStyle: 'expanded'}).on('error', sass.logError))
      .pipe(autoprefixer('last 2 versions'))
      .pipe(gulp.dest('./assets/css/'))
      .pipe(browserSync.stream())
      .pipe(rename({suffix: '.min'}))
      .pipe(cleanCSS('level: 2'))
      .pipe(gulp.dest('./assets/css/'))
      .pipe(notify({ message: 'Styles task complete' }));
  });
gulp.task('serve', function() {
    browserSync.init({
        proxy: "yoursite.test"
    });
    // Watch .scss files
    gulp.watch(['./assets/css/method-gallery.scss', '!./node_modules/', '!./.git/'], gulp.series('styles'));
    gulp.watch(['./**/*.*', '!./node_modules/', '!./.git/', '!./**/*.scss', '!./assets/css/method-gallery.css', '!./assets/css/method-gallery.css']).on('change', browserSync.reload);
});

gulp.task('watch', function() {
      // Watch .scss files
      gulp.watch(['./**/*.scss', '!./node_modules/', '!./.git/'], gulp.series('styles'));
});
