const { defineConfig } = require("@vue/cli-service");
module.exports = defineConfig({
  configureWebpack: {
    output: {
      filename: "js/[name].js",
      chunkFilename: "js/[name].js",
    },
  },
  filenameHashing: false,
  transpileDependencies: true,
});
