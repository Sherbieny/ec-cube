/**
 * Plugin JS initializer for the Shared scope.
 */
const SharedTest = require("./shared-test.js");

document.addEventListener("DOMContentLoaded", () => {
    console.log("TestPluginTest JS initialized");
    const testInstance = new SharedTest();
    testInstance.testMethod();
});
