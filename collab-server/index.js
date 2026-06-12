import {Server} from "@hocuspocus/server";
import axios from "axios";
import * as cookie from "cookie";
import 'dotenv/config'

const API_BASE_URL = process.env.API_BASE_URL;
console.log(API_BASE_URL);

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
    const ytext = document.getText("");
    const delta = ytext.toDelta();
    const nullInserts = delta.filter(op => op.insert === null);
    if (nullInserts.length > 0) {
        console.log("[pre save] WARNING: null inserts in delta!", JSON.stringify(nullInserts, null, 2));
    }
    console.log("[pre save] content =>", JSON.stringify(delta, null, 2));


    const previewText = delta
        ?.map(op => typeof op.insert === 'string' ? op.insert : '')
        .join('')
        .replace(/[\r\n]+/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .slice(0, 200);


    const xsrfToken = getXsrfToken(headers);

    try {
        const response = await axios.put(
            `${API_BASE_URL}/v1/notes/${noteId}`,
            {
                content: delta,
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
        console.log("[save] success:", response.data.data.content);
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
            console.log("[Load] content type:", typeof note.content, "isArray:", Array.isArray(note.content));
            if (Array.isArray(note.content) && note.content.length > 0) {
                const nullInserts = note.content.filter(op => op.insert === null);
                if (nullInserts.length > 0) {
                    console.log("[Load] WARNING: null inserts found in loaded content!", JSON.stringify(nullInserts, null, 2));
                }

                const content = note.content.map(op => {
                    if (op.insert === null || op.insert === undefined) {
                        return { insert: "\n", ...(op.attributes ? { attributes: op.attributes } : {}) };
                    }
                    return op;
                });

                const lastOp = content[content.length - 1];
                if (typeof lastOp.insert === 'string' && !lastOp.insert.endsWith('\n')) {
                    content.push({ insert: "\n" });
                }

                console.log("[Load] Normalized =>",
                    JSON.stringify(content, null, 2)
                );
                const ytext = data.document.getText("");
                ytext.applyDelta(content);
            }

        } catch (error) {
            console.error("[load] error:", error.response?.data || error);
        }
    },

    async onStoreDocument(data) {
        const noteId = data.documentName;
        const headers = documentHeaders.get(noteId) || {};

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
