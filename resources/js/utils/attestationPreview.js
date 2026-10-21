function bytesStartWith(bytes, signature) {
    if (bytes.length < signature.length) return false;
    return signature.every((value, index) => bytes[index] === value);
}

function textHead(bytes, length = 80) {
    return new TextDecoder("utf-8", { fatal: false })
        .decode(bytes.slice(0, length))
        .trim();
}

function base64ToBytes(value) {
    const compact = String(value).replace(/\s+/g, "");
    const binary = atob(compact);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

export function sniffAttestationBytes(bytes) {
    if (bytesStartWith(bytes, [0xff, 0xd8, 0xff])) {
        return { kind: "image", mime: "image/jpeg" };
    }
    if (bytesStartWith(bytes, [0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a])) {
        return { kind: "image", mime: "image/png" };
    }
    if (bytesStartWith(bytes, [0x47, 0x49, 0x46, 0x38])) {
        return { kind: "image", mime: "image/gif" };
    }
    if (bytesStartWith(bytes, [0x25, 0x50, 0x44, 0x46])) {
        return { kind: "pdf", mime: "application/pdf" };
    }

    const head = textHead(bytes);
    if (head.startsWith("<")) {
        return { kind: "html", mime: "text/html" };
    }
    if (head.startsWith("{") || head.startsWith("[")) {
        return { kind: "json", mime: "application/json" };
    }
    if (head.startsWith("data:image/")) {
        return { kind: "dataurl", mime: "image/jpeg" };
    }
    if (head.startsWith("data:application/pdf")) {
        return { kind: "dataurl", mime: "application/pdf" };
    }
    if (head.startsWith("/9j/")) {
        return { kind: "base64", mime: "image/jpeg" };
    }
    if (head.startsWith("iVBORw")) {
        return { kind: "base64", mime: "image/png" };
    }
    if (head.startsWith("JVBERi")) {
        return { kind: "base64", mime: "application/pdf" };
    }

    return { kind: "unknown", mime: "application/octet-stream" };
}

function extractPrintedPayload(json) {
    const printed =
        json?.data?.printed_certificate ??
        json?.printed_certificate ??
        json?.data?.data?.printed_certificate ??
        json?.data?.attributes?.printed_certificate ??
        json?.data?.download_link ??
        json?.download_link ??
        null;
    if (printed && typeof printed === "object") {
        return printed.url ?? printed.content ?? printed.data ?? printed.base64 ?? null;
    }
    return typeof printed === "string" ? printed : null;
}

function previewFromBytes(bytes) {
    const sniff = sniffAttestationBytes(bytes);
    if (sniff.kind === "image" || sniff.kind === "pdf" || sniff.kind === "html") {
        return {
            kind: sniff.kind,
            mime: sniff.mime,
            blob: new Blob([bytes], { type: sniff.mime }),
        };
    }
    return null;
}

export function prepareAttestationPreview(buffer) {
    const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
    const sniff = sniffAttestationBytes(bytes);

    if (sniff.kind === "json") {
        try {
            const json = JSON.parse(new TextDecoder().decode(bytes));
            const payload = extractPrintedPayload(json);
            if (typeof payload === "string" && payload !== "") {
                return prepareAttestationPreviewFromString(payload);
            }
        } catch {
            /* ignore */
        }
        return { kind: "error" };
    }

    if (sniff.kind === "base64") {
        try {
            return prepareAttestationPreview(base64ToBytes(new TextDecoder().decode(bytes)));
        } catch {
            return { kind: "error" };
        }
    }

    if (sniff.kind === "dataurl") {
        return prepareAttestationPreviewFromString(new TextDecoder().decode(bytes).trim());
    }

    return previewFromBytes(bytes) ?? { kind: "error" };
}

function prepareAttestationPreviewFromString(value) {
    const printed = String(value).trim();
    if (printed.startsWith("http://") || printed.startsWith("https://")) {
        return { kind: "error" };
    }
    const dataUrl = printed.match(/^data:([^;]+);base64,(.+)$/s);
    if (dataUrl) {
        try {
            return prepareAttestationPreview(base64ToBytes(dataUrl[2]));
        } catch {
            return { kind: "error" };
        }
    }
    try {
        return prepareAttestationPreview(base64ToBytes(printed));
    } catch {
        return { kind: "error" };
    }
}
