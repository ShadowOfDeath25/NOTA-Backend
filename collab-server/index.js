import {Server} from "@hocuspocus/server";

console.log("test")
const server = new Server({
    port: 1234,
    debounce: 5,
    async onAuthenticate(data) {
        console.log(data.headers.cookie)
    }
})

server.listen()
