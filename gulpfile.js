var isRelease = false;
var requireDir = require('require-dir');

// Require all tasks in gulp/tasks, including subfolders
require('./gulp/tasks/sass');
require('./gulp/tasks/uglify');
require('./gulp/tasks/watch');
require('./gulp/tasks/release');
require('./gulp/tasks/default');
