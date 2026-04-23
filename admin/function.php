<?php


    function uploadFiles($uploadedFiles) {
        $files = array();
        $errors = array();
        $returnFiles = array();
        //Xử lý gom dữ liệu vào từng file đã upload
        // var_dump($uploadedFiles);exit;

        //chuyển hoá thành dạng key và values
        foreach ($uploadedFiles as $key => $values) {
            if(is_array($values)){
                foreach ($values as $index => $value) {
                    $files[$index][$key] = $value;
                }
            }else{
                $files[$key] = $values;
            }
        }
        // kết quả quy ra rõ ràng hơn từng thuộc tính và từng giá trị
        $uploadPath = "uploads";


        //kiểm tra tồn tại folder
//        if (!is_dir($uploadPath)) {
//            mkdir($uploadPath, 0777, true);
//            //chưa có sẽ dùng hàm này tạo
//        }



        if(is_array(reset($files))){ //Up nhiều ảnh
            foreach ($files as $file) {
                $result = processUploadFile($file,$uploadPath);
                if($result['error']){
                    $errors[] = $result['message'];
                }else{
                    $returnFiles[] = $result['path'];
                }
            }
        }else{ //Up 1 ảnh
            $result = processUploadFile($files,$uploadPath);
            if($result['error']){
                return array(
                    'errors' => $result['message']
                );
            }else{
                return array(
                    'path' => $result['path']
                );
            }
        }
        return array(
            'errors' => $errors,
            'uploaded_files' => $returnFiles
        );
    }

    function processUploadFile($file,$uploadPath){
        $file = validateUploadFile($file, $uploadPath);
        if ($file != false) {
//            $file["name"] = str_replace(' ','_',$file["name"]);
            if(move_uploaded_file($file["tmp_name"], $uploadPath . '/' . $file["name"])){
                return array(
                    'error'=>false,
                    'path' =>  $uploadPath . '/' . $file["name"]
                );
            }
        }else{
            return array(
                'error'=>false,
                'message' => "File tải lên " . basename($file["name"]) . " không hợp lệ."
            );
        }
    }

//Check file hợp lệ
    function validateUploadFile($file, $uploadPath) {
        //Kiểm tra xem có vượt quá dung lượng cho phép không?
        if ($file['size'] > 2 * 1024 * 1024) { //max upload is 2 Mb = 2 * 1024 kb * 1024 bite
            return false;
        }
        //Kiểm tra xem kiểu file có hợp lệ không?
        $validTypes = array("jpg", "jpeg", "png", "bmp","xlsx","xls");
        $fileType = strtolower(substr($file['name'], strrpos($file['name'], ".") + 1));
        if (!in_array($fileType, $validTypes)) {
            return false;
        }
//        Check xem file đã tồn tại chưa? Nếu tồn tại thì đổi tên
//        $num = 0;
//        $fileName = substr($file['name'], 0, strrpos($file['name'], "."));
//        while (file_exists($uploadPath . '/' . $fileName . '.' . $fileType)) {
//            $fileName = $fileName . "(" . $num . ")";
//            $num++;
//        }
//        if($num != 0){
//            $fileName = substr($file['name'], 0, strrpos($file['name'], ".")). "(" . $num . ")";
//        }else{
//            $fileName = substr($file['name'], 0, strrpos($file['name'], "."));
//        }
//        $file['name'] =  $fileName . '.' . $fileType;
        return $file;
    }



    function validateDateTime($date) {
        //Kiểm tra định dạng ngày tháng xem đúng DD/MM/YYYY hay chưa?
        preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{4}$/', $date, $matches);
        if (count($matches) == 0) { //Nếu ngày tháng nhập không đúng định dạng thì $match = array(); (rỗng)
            return false;
        }
        $separateDate = explode('-', $date);
        $day = (int) $separateDate[0];
        $month = (int) $separateDate[1];
        $year = (int) $separateDate[2];
        //Nếu là tháng 2
        if ($month == 2) {
            if ($year % 4 == 0) { //Nếu là năm nhuận
                if ($day > 29) {
                    return false;
                }
            } else { //Không phải năm nhuận
                if ($day > 28) {
                    return false;
                }
            }
        }
        //Check các tháng khác
        switch ($month) {
            case 1:
            case 3:
            case 5:
            case 7:
            case 8:
            case 10:
            case 12:
                if ($day > 31) {
                    return false;
                }
                break;
            case 4:
            case 6:
            case 9:
            case 11:
                if ($day > 30) {
                    return false;
                }
                break;
        }
        return true;
    }

?>