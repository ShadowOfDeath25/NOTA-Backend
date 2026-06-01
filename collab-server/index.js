import {Server} from "@hocuspocus/server";
import * as Y from "yjs";
import axios from "axios";
import * as cookie from "cookie";
import 'dotenv/config'


const API_BASE_URL = process.env.API_BASE_URL;
console.log(API_BASE_URL)
const debounceMap = new Map();
const documentHeaders = new Map();
const parseHeaders = (requestHeaders) => {

    if (typeof requestHeaders?.entries === 'function') {
        return Object.fromEntries(requestHeaders.entries());
    }
    return Object.fromEntries(
        Object.entries(requestHeaders).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v])
    );
};

const getXsrfToken = (headers) => {
    try {
        const raw = headers.cookie || "";
        return cookie.parse(raw)["XSRF-TOKEN"] || "";
    } catch {
        return "";
    }
};


const saveDocument = async (noteId, document, headers) => {
    const update = Y.encodeStateAsUpdate(document);

    const previewText = document
        .getText("")
        .toString()
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 200);

    const base64 = Buffer.from(update).toString("base64");

    const xsrfToken = getXsrfToken(headers);

    try {
        const response = await axios.put(
            `${API_BASE_URL}/v1/notes/${noteId}`,
            {
                content: base64,
                preview: previewText
            },
            {
                headers: {
                    ...headers,
                    "X-XSRF-TOKEN": decodeURIComponent(xsrfToken),
                },
                withCredentials: true,
            }
        );
        console.log("[save] success:", response.data);
    } catch (e) {
        console.error("[save] error:", e.response?.data || e.message);
    }
};

const server = new Server({
    port: 1234,

    async onConnect(data) {
        console.log("[connect] client connected, doc:", data.documentName);
        const headers = parseHeaders(data.requestHeaders);
        documentHeaders.set(data.documentName, headers);
        console.log("[connect] cookies received:", headers.cookie ? "yes" : "NO COOKIES");
    },

    async onDisconnect(data) {
        const noteId = data.documentName;
        const headers = documentHeaders.get(noteId) || {};


        if (debounceMap.has(noteId)) {
            clearTimeout(debounceMap.get(noteId));
            debounceMap.delete(noteId);
        }

        await saveDocument(noteId, data.document, headers);
        documentHeaders.delete(noteId);
    },

    async onLoadDocument(data) {
        const headers = parseHeaders(data.requestHeaders);
        documentHeaders.set(data.documentName, headers);



        console.log("[load] cookie:", headers.cookie ? "yes" : "MISSING");

        try {
            const response = await axios.get(
                `${API_BASE_URL}/v1/notes/${data.documentName}`,
                {
                    headers,
                    withCredentials: true,
                }
            );
            const note = response.data.data;
            if (note?.content) {
                const binary = Buffer.from(note.content, "base64");
                const update = new Uint8Array(binary);
                Y.applyUpdate(data.document, update);
            }
        } catch (error) {
            console.error("[load] error:", error.response?.data || error.message);
        }
    },

    async onStoreDocument(data) {
        const noteId = data.documentName;
        const headers = documentHeaders.get(noteId) || {};

        console.log(JSON.stringify(data.document, null, 2));
        if (debounceMap.has(noteId)) {
            clearTimeout(debounceMap.get(noteId));
        }

        const timeout = setTimeout(() => {
            saveDocument(noteId, data.document, headers);
        }, 5000);

        debounceMap.set(noteId, timeout);
    },
});

server.listen();
console.log("Hocuspocus running on ws://localhost:1234");
