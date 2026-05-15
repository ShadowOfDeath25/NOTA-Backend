import {Server} from "@hocuspocus/server";
import * as Y from 'yjs';
import axios from "axios";
import * as cookie from "cookie";

let headers = {};

const debounceMap = new Map();

const saveDocument = async (noteId, document, headers) => {
    const update = Y.encodeStateAsUpdate(document)
    const base64 = Buffer.from(update).toString("base64")
    try {

        await axios.put(`http://localhost:8000/api/v1/notes/${noteId}`, {
            content: base64
        }, {
            headers: {
                ...headers,
                'X-XSRF-TOKEN': cookie.parse(headers.cookie)['XSRF-TOKEN']
            },
            withCredentials: true,
            withXSRFToken: true
        })


    } catch (e) {
        console.log(e)
    }

}

const server = new Server({
    port: 1234,

    onConnect: () => {
        console.log("client connected")
    },

    async onDisconnect(data) {
        const noteId = data.documentName;
        if (debounceMap.has(noteId)) {
            clearTimeout(debounceMap.get(noteId))
        }
        await saveDocument(noteId, data.document, headers)
    },
    async onLoadDocument(data) {
        headers = Object.fromEntries(data.requestHeaders);


        try {
            const response = await axios.get(
                `http://localhost:8000/api/v1/notes/${data.documentName}`,
                {
                    headers: headers,
                    withCredentials: true,

                }
            )

            const note = response.data.data;


            if (note?.content) {
                const binary = Buffer.from(note.content, "base64")
                const update = new Uint8Array(binary)

                Y.applyUpdate(data.document, update)
            }


        } catch (error) {
            console.log(error.response?.data || error)
        }

    },
    async onStoreDocument(data) {
        const noteId = data.documentName;
        if (debounceMap.has(noteId)) {
            clearTimeout(debounceMap.get(noteId));
        }

        const timeout = setTimeout(() => {
            saveDocument(noteId, data.document, headers)
        }, 5000);

        debounceMap.set(noteId, timeout)


    },


});

server.listen();

console.log("Hocuspocus running on ws://localhost:1234");
