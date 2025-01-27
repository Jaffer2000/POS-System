const { exec } = require("child_process");
const fs = require("fs-extra");
const path = require("path");

// Paths for source and destination files
const distDir = path.join(__dirname, "dist/js");
const appOutput = path.join(__dirname, "/../views/js/app/app.js");
const vendorOutput = path.join(__dirname, "/../views/js/app/vendor.js");

// Ensure destination directories exist
fs.ensureDirSync(path.dirname(appOutput));
fs.ensureDirSync(path.dirname(vendorOutput));

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

  // Move app.js and vendor.js to the destination
  console.log("Moving build artifacts...");
  const appSource = path.join(distDir, "app.js");
  const vendorSource = path.join(distDir, "chunk-vendors.js");

  try {
    if (fs.existsSync(appSource)) {
      fs.copySync(appSource, appOutput);
      console.log(`app.js moved to ${appOutput}`);
    } else {
      console.error("app.js not found in dist folder.");
    }

    if (fs.existsSync(vendorSource)) {
      fs.copySync(vendorSource, vendorOutput);
      console.log(`vendor.js moved to ${vendorOutput}`);
    } else {
      console.error("vendor.js not found in dist folder.");
    }
  } catch (err) {
    console.error(`Error moving files: ${err.message}`);
  }
});
