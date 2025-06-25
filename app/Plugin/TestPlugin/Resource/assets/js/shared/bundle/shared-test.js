class SharedTest {
    constructor() {
        console.log("SharedTest class initialized");
        this.test = "test";
    }
    testMethod() {
        console.log("SharedTest method called");
    }
}

module.exports = SharedTest;
