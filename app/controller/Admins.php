<?php
class Admins extends Controller
{
    private $adminModel;
    private $studentModel;

    public function __construct()
    {
        /**
         * for extra control, set a session of the following attributes:
         * 
         * 1. valid_actor : if the user actually successfully logged in
         * 2. current_actor : not logged in, but casually browsing content as 'the' user
         * 3. current_controller : generally to give hint to what page the user is currently in
         * 
         */
        $this->adminModel = $this->model('Admin');
    }

    public function topicForm($darjahId)
    {
        $data = [
            'topicName' => '',
            'darjahId' => $darjahId,
            'pdf_content' => '',
            'summary' => '',
            'fileName' => '',

            'topicName_err' => '',
            'pdf_content_err' => '',
            'summary_err' => '',
            'fileName_err' => ''
        ];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // check necessary error:
            if (empty($_POST['topicName'])) {
                $data['topicName_err'] = 'Sila masukkan tajuk';
            } else {
                $data['topicName'] = $_POST['topicName'];
            }

            if (empty($_POST['summary'])) {
                $data['summary_err'] = 'Sila masukkan huraian';
            } else {
                $data['summary'] = $_POST['summary'];
            }

            if (empty($data['summary_err']) && empty($data['topicName_err'])) {
                // if not empty then we can proceed with file processing
                if ($_FILES['pdfFile']['error'] == UPLOAD_ERR_OK) {
                    $tmpFileName = $_FILES['pdfFile']['tmp_name'];
                    // Check if the uploaded file is a PDF
                    $fileType = mime_content_type($tmpFileName);
                    if ($fileType === 'application/pdf') {
                        // Read the content of the PDF file
                        $fileContent = file_get_contents($tmpFileName);
                        $data['pdf_content'] = $fileContent;
                        // Store file information in the database
                        if ($this->adminModel->addTopic($data)) {
                            $this->darjah($data['darjahId']);
                        } else {
                            die('failed to stored pdf to database');
                        }
                    } else {
                        die('invalid file type');
                    }
                } else {
                    $data['fileName_err'] = 'Sila masukkan fail';
                    return $this->view('admin/topicForm', $data);
                }
            } else {
                // load views with error
                return $this->view('admin/topicForm', $data);
            }
        } else {
            return $this->view('admin/topicForm', $data);
        }
    }

    public function deleteMaterials($topicId)
    {
        $darjahId = $_GET['darjahId'];
        if (!empty($darjahId) && !empty($topicId)) {
            $this->adminModel->deleteMaterials($topicId);
            redirect('admins/darjah/' . $darjahId);
        } else {
            die('some values went missing');
        }
    }

    public function deleteSOW($darjahId)
    {
        $this->adminModel->deleteSOW($darjahId);
        redirect('admins/darjah/' . $darjahId);
    }

    public function uploadSOW($darjahId)
    {
        $darjahObject = $this->adminModel->getDarjahDetails($darjahId);

        $data = [
            'darjahId' => $darjahId,
            'topicList' => $this->adminModel->topicList($darjahId),
            'summary' => $darjahObject->summary,
            'file' => $darjahObject->pdf_notes,
            'fileName' => '',
            'fileContent' => '',

            'fileName_err' => ''
        ];

        $data['darjahId'] = $darjahId;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (empty($_POST['fileName'])) {
                $data['fileName_err'] = 'Sila masukkan nama fail';
                return $this->view('admin/darjahDetails', $data);
            } else {
                $data['fileName'] = $_POST['fileName'];
            }

            if (empty($data['filenName_err'])) {
                if ($_FILES['pdfFile']['error'] == UPLOAD_ERR_OK) {
                    $tmpFileName = $_FILES['pdfFile']['tmp_name'];
                    // Check if the uploaded file is a PDF
                    $fileType = mime_content_type($tmpFileName);

                    if ($fileType === 'application/pdf') {
                        // Read the content of the PDF file
                        $fileContent = file_get_contents($tmpFileName);
                        $data['fileContent'] = $fileContent;

                        // Store file information in the database
                        if ($this->adminModel->uploadSOW($data)) {
                            redirect('admins/darjah/' . $darjahId);
                        } else {
                            die('failed to stored pdf to database');
                        }
                    } else {
                        die('invalid file type');
                    }
                } else {
                    $data['fileName_err'] = 'Sila masukkan fail';
                    return $this->view('admin/darjahDetails', $data);
                }
            }
        }
    }

    public function testFetchPdf($topicId)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            $data = $this->adminModel->testFetchPdf($topicId);

            if ($data !== false) {
                // Send appropriate headers for a PDF file
                header('Content-Type: application/pdf');

                // Use "inline" to view the PDF in the browser
                // User "attachment" to actually download the PDF file
                header('Content-Disposition: inline; filename="' . $data['pdfName'] . '.pdf"');
                //header('Content-Disposition: attachment; filename="' . $data['pdfName'] . '');

                // Output the PDF data
                // actually send this data to view, so that it can be preview
                echo $data['pdfContent'];
            } else {
                // Handle if the record with the specified topicId is not found
                echo "PDF not found for the given topicId.";
            }
        }
    }

    public function testSubmitPdf()
    {
        // need to check first if there is already a pdf for that 'darjah'
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Check if the file was uploaded without errors
            if ($_FILES['pdfFile']['error'] == UPLOAD_ERR_OK) {
                // Get the temporary name of the uploaded file
                $tmpFileName = $_FILES['pdfFile']['tmp_name'];

                // Check if the uploaded file is a PDF
                $fileType = mime_content_type($tmpFileName);
                if ($fileType === 'application/pdf') {
                    // Read the content of the PDF file
                    $fileContent = file_get_contents($tmpFileName);

                    // Store file information in the database
                    if ($this->adminModel->testStorePdf($_FILES['pdfFile']['name'], $fileContent)) {
                        die('successfully stored pdf to database');
                    } else {
                        die('failed to stored pdf to database');
                    }
                } else {
                    die('invalid file type');
                }
            } else {
                // Handle upload errors
                die('upload errors');
            }
        }
    }

    public function index()
    {
        //init data
        $data = [
            'username' => '',
            'password' => '',

            'username_err' => '',
            'password_err' => '',
        ];

        //load view
        $this->view('admin/login', $data);
    }

    public function home()
    {
        return $this->view('admin/home');
    }

    public function darjah($darjahId)
    {
        $darjahObject = $this->adminModel->getDarjahDetails($darjahId);
        $feedbacks = $this->studentModel->getFeedbacksFromDarjah($darjahId);

        $data = [
            'darjahId' => $darjahId,
            'topicList' => $this->adminModel->topicList($darjahId),
            'summary' => $darjahObject->summary,
            'file' => $darjahObject->pdf_notes,
            'file_name' => $darjahObject->pdf_name,
            'feedbacks' => $feedbacks
        ];

        return $this->view('admin/darjahDetails', $data);
    }

    public function login()
    {
        //check for POST
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //process the login form
            //sanitize POST data
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            //init data
            $data = [
                'username' => trim($_POST['username']),
                'password' => trim($_POST['password']),

                'username_err' => '',
                'password_err' => '',
            ];

            if (empty($data['username'])) {
                $data['username_err'] = 'Please enter username';
            }

            if (empty($data['password'])) {
                $data['password_err'] = 'Please enter your password';
            }

            //check for admin username
            if ($this->adminModel->findAdminByUsername($data['username'])) {
                //user found
            } else {
                $data['username_err'] = '*Nama-pengguna tidak tersenarai, sila periksa';
            }

            //make sure errors are empty
            if (empty($data['username_err']) && empty($data['password_err'])) {
                //validated
                //Check and set logged in user

                $loggedInUser = $this->adminModel->login($data['username'], $data['password']);
                if ($loggedInUser) {
                    // adminObj is used to display admin profile for view purpose
                    $adminObj = $this->adminModel->getAdminDetails($data['username']);

                    if (isset($_SESSION['currentUser'])) {
                        unset($_SESSION['currentUser']);
                    }
                    //create session
                    $_SESSION['currentUser'] = $adminObj;

                    redirect('admins/home');
                } else {
                    $data['password_err'] = '*Kata laluan salah, sila periksa';
                    $this->view('admin/login', $data);
                }
            } else {
                //Load view with errors
                $this->view('admin/login', $data);
            }
        } else {
            //init data
            $data = [
                'username' => '',
                'password' => '',

                'username_err' => '',
                'password_err' => '',
            ];

            //load view
            $this->view('admin/login', $data);
        }
    }
}
