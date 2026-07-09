function pad(value, size) {
	return String(value).padStart(size, "0");
}

function generatedName(filePath, fileType) {
	const now = new Date();
	const stamp =
		`${now.getFullYear()}${pad(now.getMonth() + 1, 2)}${pad(now.getDate(), 2)}` +
		`${pad(now.getHours(), 2)}${pad(now.getMinutes(), 2)}${pad(now.getSeconds(), 2)}` +
		`${pad(now.getMilliseconds(), 3)}`;
	const random = Math.random().toString(36).slice(2, 8);
	return `upload_${stamp}_${random}.${getUploadFileExtension(filePath, fileType)}`;
}

function decodeName(name) {
	try {
		return decodeURIComponent(name);
	} catch (e) {
		return name;
	}
}

function limitFileName(name, maxLength) {
	if (name.length <= maxLength) return name;
	const dotIndex = name.lastIndexOf(".");
	if (dotIndex <= 0) return name.slice(0, maxLength);
	const ext = name.slice(dotIndex);
	const base = name.slice(0, dotIndex);
	return `${base.slice(0, Math.max(1, maxLength - ext.length))}${ext}`;
}

function getFileNameFromPath(filePath) {
	const cleanPath = String(filePath || "").split("?")[0].split("#")[0];
	const parts = cleanPath.split(/[\\/]/);
	return parts[parts.length - 1] || "";
}

function cleanFileName(name) {
	let result = decodeName(String(name || ""));
	result = result.split("?")[0].split("#")[0];
	result = result.split(/[\\/]/).pop() || "";
	result = result
		.replace(/[\\/:*?"<>|\x00-\x1F]/g, "_")
		.replace(/\s+/g, " ")
		.trim()
		.replace(/^\.+/, "")
		.replace(/\.+$/, "");
	return limitFileName(result, 120);
}

function hasFileExtension(name) {
	return /\.[a-zA-Z0-9]{1,8}$/.test(String(name || ""));
}

function isTempFileName(name) {
	const base = String(name || "")
		.replace(/\.[^.]*$/, "")
		.toLowerCase();
	if (!base) return true;
	if (/^(tmp|temp|compressed|wxfile)[_-]/.test(base)) return true;
	if (/^tmp[a-z0-9_-]{8,}$/.test(base)) return true;
	if (/^[a-f0-9]{24,}$/.test(base)) return true;
	return false;
}

function withExtension(name, filePath, fileType) {
	const cleanName = cleanFileName(name);
	if (!cleanName) return "";
	if (hasFileExtension(cleanName)) return cleanName;
	return `${cleanName}.${getUploadFileExtension(filePath, fileType)}`;
}

export function getUploadFileExtension(filePath, fileType = 1) {
	if (Number(fileType) === 2) return "mp4";
	const cleanPath = String(filePath || "").split("?")[0].split("#")[0];
	const match = cleanPath.match(/\.([a-zA-Z0-9]{1,8})$/);
	return match ? match[1].toLowerCase() : "jpg";
}

export function normalizeSelectedUploadFile(file, fileType = 1) {
	const source = file && typeof file === "object" ? file : {};
	const filePath =
		(typeof file === "string" ? file : "") ||
		source.path ||
		source.tempFilePath ||
		source.filePath ||
		"";
	return {
		path: filePath,
		name: getSelectedUploadFileName(source, filePath, fileType),
		size: Number(source.size || source.fileSize || source.file_size || 0),
	};
}

export function getSelectedUploadFileName(file, filePath = "", fileType = 1) {
	const source = file && typeof file === "object" ? file : {};
	const candidates = [
		source.name,
		source.fileName,
		source.originalName,
		source.original_name,
		source.filename,
		source.file_name,
	];
	for (let i = 0; i < candidates.length; i++) {
		const name = withExtension(candidates[i], filePath, fileType);
		if (name && !isTempFileName(name)) return name;
	}
	const pathName = withExtension(getFileNameFromPath(filePath), filePath, fileType);
	if (pathName && !isTempFileName(pathName)) return pathName;
	return generatedName(filePath, fileType);
}

export function buildUploadNameFormData(uploadName) {
	return {
		filename: uploadName,
		file_name: uploadName,
		original_name: uploadName,
		name: uploadName,
	};
}

export function prepareNamedUploadFile(filePath, uploadName) {
	return new Promise((resolve) => {
		if (
			typeof wx === "undefined" ||
			!wx.getFileSystemManager ||
			!wx.env ||
			!wx.env.USER_DATA_PATH ||
			!uploadName
		) {
			resolve(filePath);
			return;
		}
		const fs = wx.getFileSystemManager();
		const destPath = `${wx.env.USER_DATA_PATH}/${uploadName}`;
		fs.copyFile({
			srcPath: filePath,
			destPath,
			success: () => resolve(destPath),
			fail: () => resolve(filePath),
		});
	});
}
