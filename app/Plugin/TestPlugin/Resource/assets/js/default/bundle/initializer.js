/**
 * Plugin JS initializer for the Default scope.
 */
const DefaultTest = require("./default-test.js");

document.addEventListener("DOMContentLoaded", () => {
    console.log("TestPluginTest JS initialized");
    const testInstance = new DefaultTest();
    testInstance.testMethod();
});
