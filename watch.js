// Auto-sync using Node.js (if you have it installed)
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');

const watchDir = './';
const excludeDirs = ['node_modules', '.git', 'vendor', 'backups'];

console.log('🔍 Watching for file changes...');

fs.watch(watchDir, { recursive: true }, (eventType, filename) => {
    if (filename && !excludeDirs.some(dir => filename.includes(dir))) {
        console.log(`📝 File changed: ${filename}`);
        
        // Run sync after 2 seconds delay
        setTimeout(() => {
            exec('php sync.php', (error, stdout, stderr) => {
                if (error) {
                    console.error(`❌ Sync error: ${error}`);
                    return;
                }
                console.log(stdout);
            });
        }, 2000);
    }
});

console.log('Press Ctrl+C to stop watching');