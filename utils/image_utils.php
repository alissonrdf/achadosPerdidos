<?php

/**
 * Processa e salva uma imagem no formato WebP com redimensionamento e compressão
 * 
 * @param array $file Arquivo enviado via $_FILES
 * @param string $targetPath Caminho completo onde a imagem WebP será salva
 * @param int $maxWidth Largura máxima da imagem processada
 * @param int $maxHeight Altura máxima da imagem processada
 * @param int $quality Qualidade da imagem WebP (0-100)
 * 
 * @return string|false Caminho da imagem WebP salva ou false em caso de erro
 */
function processImage($file, $targetPath, $maxWidth = 800, $maxHeight = 800, $quality = 75) {
    // Verificar o tipo de imagem
    $imageInfo = getimagesize($file['tmp_name']);
    $mime = $imageInfo['mime'];

    // Se o arquivo já estiver no formato WebP e dentro do tamanho desejado, apenas mova-o
    if ($mime === 'image/webp') {
        list($width, $height) = getimagesize($file['tmp_name']);
        if ($width <= $maxWidth && $height <= $maxHeight) {
            move_uploaded_file($file['tmp_name'], $targetPath);
            return $targetPath;
        }
    }

    // Criar uma imagem com base no tipo MIME
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
        case 'image/bmp':
            $image = imagecreatefrombmp($file['tmp_name']);
            break;
        default:
            return false; // Tipo de imagem não suportado
    }

    // Obter largura e altura originais
    $width = imagesx($image);
    $height = imagesy($image);

    // Calcular nova largura e altura mantendo a proporção
    $scale = min($maxWidth / $width, $maxHeight / $height);
    if ($scale < 1) {
        $newWidth = floor($width * $scale);
        $newHeight = floor($height * $scale);
    } else {
        $newWidth = $width;
        $newHeight = $height;
    }

    // Criar imagem redimensionada
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    // Preservar transparência para PNG ou GIF
    if (in_array($mime, ['image/png', 'image/gif'])) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
    }
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Definir o caminho para salvar a imagem em WebP
    $webpPath = preg_replace('/\.[a-z]+$/i', '.webp', $targetPath);

    // Salvar a imagem como WebP com a qualidade especificada
    imagewebp($newImage, $webpPath, $quality);

    // Liberar memória
    imagedestroy($image);
    imagedestroy($newImage);

    return $webpPath;
}

/**
 * Gera um nome de arquivo seguro e único para a imagem com base em um nome de referência.
 * 
 * @param string $referenceName Nome de referência (ex.: nome do item ou categoria)
 * @return string Nome seguro para a imagem com extensão .webp
 */
function generateSafeImageName($referenceName) {
    // Remove caracteres especiais e substitui espaços por underscores
    $sanitized = preg_replace('/[^A-Za-z0-9_\-]/', '_', $referenceName);
    return $sanitized . '_' . uniqid() . '.webp';
}