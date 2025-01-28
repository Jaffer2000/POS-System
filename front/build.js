const { exec } = require("child_process");
const fs = require("fs-extra");
const path = require("path");

// Paths for source and destination files
const distDir = path.join(__dirname, "dist/js");
const viewsDir = path.join(__dirname, "/../views/js/app");

// Ensure destination directories exist
fs.ensureDirSync(viewsDir);

// Run the build command
console.log("Running npm build...");
exec("npm run build", (error, stdout, stderr) => {
  if (error) {
    console.error(`Build failed: ${error.message}`);
    return;
  }
  if (stderr) {
    console.error(`Build stderr: ${stderr}`);
  }
  console.log(stdout);

  // Files to move
  const filesToMove = [
    "app.js",
    "app.js.map",
    "chunk-vendors.js",
    "chunk-vendors.js.map",
  ];

  console.log("Moving build artifacts...");
  filesToMove.forEach((file) => {
    const sourcePath = path.join(distDir, file);
    const destPath = path.join(viewsDir, file);

    try {
      if (fs.existsSync(sourcePath)) {
        fs.copySync(sourcePath, destPath);
        console.log(`${file} moved to ${destPath}`);
      } else {
        console.error(`${file} not found in dist folder.`);
      }
    } catch (err) {
      console.error(`Error moving ${file}: ${err.message}`);
    }
  });
});
