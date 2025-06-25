/**
 * Plugin JS initializer for the Admin scope.
 */
const AdminTest = require("./admin-test.js");

document.addEventListener("DOMContentLoaded", () => {
    console.log("TestPluginTest JS initialized");
    const testInstance = new AdminTest();
    testInstance.testMethod();
});
