// to make gulp not halt on sass errors install gulp-plumber and gulp-util look at this link http://stackoverflow.com/a/28080077
var gulp        = require('gulp'),
    plumber     = require('gulp-plumber'),
    gutil       = require('gulp-util'),
    runSequence = require('run-sequence'),
    sass        = require('gulp-sass'),
    concat      = require('gulp-concat'),
    browserSync = require('browser-sync').create(),
    minify      = require('gulp-minify'),
    merge       = require('merge-stream'),
    spawn       = require('child_process').spawn,
    notify      = require("gulp-notify");

// sass compiling + css resource merging
gulp.task('sass-compile', function() {

  var cssStream = gulp.src(
    [
      ''
    ]
  );

  //compile sass
  var sassStream = gulp.src('assets/scss/styles.scss')
      // b/o: make sass not break on errors
      .pipe(plumber(function (error) {
          gutil.log(error.message);
          this.emit('end');
      }))
      // e/o: make sass not break on errors
      .pipe(sass({
          errLogToConsole: false,
          outputStyle: 'compressed'
      }));

  //merge the two streams and concatenate their contents into a single file
  return merge(cssStream, sassStream)
      .pipe(concat('assets/css/styles.css'))
      .pipe(gulp.dest('./'))
      .pipe(notify('sass compiled'));

});
//// e/o: sass compiling

gulp.task('js-compile', function(done) {
    runSequence('js-concat', 'compress', function() {
        done();
    });
});

gulp.task('js-concat', function() {
  return gulp.src(
      [
        'assets/js/_wallet.js'
      ]
    )
    .pipe(concat('wallet.js'))
    .pipe(gulp.dest('assets/js/dist/'))
    .pipe(notify('JS Concatenated'));
});

gulp.task('compress', function() {
  gulp.src('assets/js/dist/wallet.js')
    .pipe(minify({
        ext:{
            min:'.min.js'
        },
        exclude: ['tasks'],
        ignoreFiles: ['.combo.js', '-min.js']
    }))
    .pipe(gulp.dest('assets/js/dist/'))
    .pipe(notify('JS Compressed'))
});
////

gulp.task('watch', function(){

    gulp.watch(
      [
        'assets/js/**/*.js',
        '!assets/js/dist/*.js' // not the distributed folder
      ],
      ['js-compile']
    );

    gulp.watch(
      [
        'assets/scss/**/*.scss'
      ],
      ['sass-compile']);

    gulp.watch('gulpfile.js', ['gulp-reload']);

});

gulp.task('browser-sync', function() {
    browserSync.init(["assets/css/*.css", "assets/js/*.js"], {
      open     : false,
      watchTask: true,
      proxy    : "localhost/ignitionpaper2/paper",
      port     : 7070
    });
});

gulp.task('gulp-reload', function() {
  // spawn('gulp', ['default'], {stdio: 'inherit'}); // use this to reload everything
  spawn('gulp', ['watch'], {stdio: 'inherit'});
  process.exit();
});

gulp.task('default', ['browser-sync', 'watch'], function () {
    gulp.watch("assets/scss/*.scss", ['sass-compile']);
});
