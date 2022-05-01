<?php

namespace Plugins\Api;

use Systems\AdminModule;
use Systems\MySQL;

class Admin extends AdminModule
{
    public function navigation()
    {
        return [
            'Kelola' => 'manage',
            'Notifikasi APAM' => 'notifikasi',
            'Pengaturan APAM' => 'settingsapam',
            'Payment Duitku' => 'paymentduitku',
        ];
    }

    public function getManage()
    {
      $sub_modules = [
        ['name' => 'Notifikasi APAM', 'url' => url([ADMIN, 'api', 'notifikasi']), 'icon' => 'database', 'desc' => 'Notifikasi APAM API'],
        ['name' => 'Pengaturan APAM', 'url' => url([ADMIN, 'api', 'settingsapam']), 'icon' => 'database', 'desc' => 'Pengaturan APAM API'],
        ['name' => 'Payment Duitku', 'url' => url([ADMIN, 'api', 'paymentduitku']), 'icon' => 'database', 'desc' => 'Pengaturan e-Payment API'],
      ];
      return $this->draw('manage.html', ['sub_modules' => $sub_modules]);
    }

    public function getNotifikasi()
    {

      $totalRecords = $this->mysql('mlite_notifications')
        ->select('id')
        ->toArray();
      $offset         = 10;
      $return['halaman']    = 1;
      $return['jml_halaman']    = ceil(count($totalRecords) / $offset);
      $return['jumlah_data']    = count($totalRecords);

      $return['list'] = $this->mysql('mlite_notifications')
        ->join('pasien', 'pasien.no_rkm_medis=mlite_notifications.no_rkm_medis')
        ->desc('id')
        ->limit(10)
        ->toArray();

      $this->core->addCSS(url('assets/css/bootstrap-datetimepicker.css'));
      $this->core->addJS(url('assets/jscripts/moment-with-locales.js'));
      $this->core->addJS(url('assets/jscripts/bootstrap-datetimepicker.js'));

      $this->core->addJS(url([ADMIN, 'api', 'notifikasijs']), 'footer');
      return $this->draw('notifikasi.html', [
        'notifikasi' => $return
      ]);

    }

    public function anyNotifikasiForm()
    {
        if (isset($_POST['id'])){
          $return['form'] = $this->mysql('mlite_notifications')->where('id', $_POST['id'])->oneArray();
        } else {
          $return['form'] = [
            'id' => '',
            'judul' => '',
            'pesan' => '',
            'tanggal' => '',
            'no_rkm_medis' => '',
            'status' => ''
          ];
        }

        echo $this->draw('notifikasi.form.html', ['notifikasi' => $return]);
        exit();
    }

    public function anyNotifikasiDisplay()
    {

        $perpage = '10';
        $totalRecords = $this->mysql('mlite_notifications')
          ->select('id')
          ->toArray();
        $offset         = 10;
        $return['halaman']    = 1;
        $return['jml_halaman']    = ceil(count($totalRecords) / $offset);
        $return['jumlah_data']    = count($totalRecords);

        $return['list'] = $this->mysql('mlite_notifications')
          ->join('pasien', 'pasien.no_rkm_medis=mlite_notifications.no_rkm_medis')
          ->desc('id')
          ->offset(0)
          ->limit($perpage)
          ->toArray();

        if(isset($_POST['cari'])) {
          $return['list'] = $this->mysql('mlite_notifications')
            ->join('pasien', 'pasien.no_rkm_medis=mlite_notifications.no_rkm_medis')
            ->like('id', '%'.$_POST['cari'].'%')
            ->orLike('judul', '%'.$_POST['cari'].'%')
            ->desc('id')
            ->offset(0)
            ->limit($perpage)
            ->toArray();
          $jumlah_data = count($return['list']);
          $jml_halaman = ceil($jumlah_data / $offset);
        }
        if(isset($_POST['halaman'])){
          $offset     = (($_POST['halaman'] - 1) * $perpage);
          $return['list'] = $this->mysql('mlite_notifications')
            ->join('pasien', 'pasien.no_rkm_medis=mlite_notifications.no_rkm_medis')
            ->desc('id')
            ->offset($offset)
            ->limit($perpage)
            ->toArray();
          $return['halaman'] = $_POST['halaman'];
        }

        echo $this->draw('notifikasi.display.html', ['notifikasi' => $return]);
        exit();
    }

    public function postNotifikasiSave()
    {
      if (!$this->mysql('mlite_notifications')->where('id', $_POST['id'])->oneArray()) {
        $_POST['status'] = 'unread';
        $query = $this->mysql('mlite_notifications')->save($_POST);
      } else {
        $query = $this->mysql('mlite_notifications')->where('id', $_POST['id'])->save($_POST);
      }
      return $query;
    }

    public function postNotifikasiHapus()
    {
      return $this->mysql('mlite_notifications')->where('id', $_POST['id'])->delete();
    }

    public function getNotifikasiJS()
    {
        header('Content-type: text/javascript');
        echo $this->draw(MODULES.'/api/js/admin/notifikasi.js');
        exit();
    }

    /* Settings Section */
    public function getSettingsApam()
    {
        $this->assign['title'] = 'Pengaturan Modul API';
        $this->assign['api'] = htmlspecialchars_array($this->settings('api'));
        $this->assign['penjab'] = $this->mysql('penjab')->toArray();
        return $this->draw('settings.apam.html', ['settings' => $this->assign]);
    }

    public function postSaveSettingsApam()
    {
        $cek_prop = $this->mysql('propinsi')->where('kd_prop', $_POST['api[apam_kdprop]'])->oneArray();
        if(!$cek_prop){
          $this->mysql('propinsi')->save(['kd_prop' => $_POST['api[apam_kdprop]'], 'nm_prop' => $_POST['nm_prop']]);
        }
        $cek_kab = $this->mysql('kabupaten')->where('kd_kab', $_POST['api[apam_kdkab]'])->oneArray();
        if(!$cek_kab){
          $this->mysql('kabupaten')->save(['kd_kab' => $_POST['api[apam_kdkab]'], 'nm_kab' => $_POST['nm_kab']]);
        }
        $cek_kec = $this->mysql('kecamatan')->where('kd_kec', $_POST['api[apam_kdkec]'])->oneArray();
        if(!$cek_kec){
          $this->mysql('kecamatan')->save(['kd_kec' => $_POST['api[apam_kdkec]'], 'nm_kec' => $_POST['nm_kec']]);
        }

        foreach ($_POST['api'] as $key => $val) {
            $this->settings('api', $key, $val);
        }
        $this->notify('success', 'Pengaturan telah disimpan');
        redirect(url([ADMIN, 'api', 'settingsapam']));
    }
    /* End Settings Farmasi Section */

    public function postKirimWA()
    {
        $waapitoken = $this->settings->get('settings.waapitoken');
        $waapiphonenumber = $this->settings->get('settings.waapiphonenumber');
        $url = "https://mlite.id/wagateway/kirimpesan";
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS,"sender=".$waapiphonenumber."&number=".$_POST['number']."&message=".$_POST['message']."&api_key=".$waapitoken);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);
        echo $response;
        exit();
    }

    public function postKirimWAMedia()
    {
        $waapitoken = $this->settings->get('settings.waapitoken');
        $waapiphonenumber = $this->settings->get('settings.waapiphonenumber');
        $url = "https://mlite.id/wagateway/kirimgambar";
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS,"sender=".$waapiphonenumber."&number=".$_POST['number']."&message=".$_POST['message']."&url=".$_POST['file']."&api_key=".$waapitoken);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);
        echo $response;
        exit();
    }

    public function postKirimWADocument()
    {
        $waapitoken = $this->settings->get('settings.waapitoken');
        $waapiphonenumber = $this->settings->get('settings.waapiphonenumber');
        $url = "https://mlite.id/wagateway/kirimfile";
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS,"sender=".$waapiphonenumber."&number=".$_POST['number']."&message=".$_POST['message']."&url=".$_POST['file']."&api_key=".$waapitoken);
        curl_setopt($curlHandle, CURLOPT_HEADER, 0);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT,30);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curlHandle);
        curl_close($curlHandle);
        echo $response;
        exit();
    }

    public function getPaymentDuitku()
    {

      $this->core->addCSS(url('assets/css/dataTables.bootstrap.min.css'));
      $this->core->addJS(url('assets/jscripts/jquery.dataTables.min.js'), 'footer');
      $this->core->addJS(url('assets/jscripts/dataTables.bootstrap.min.js'), 'footer');

      $return['list'] = $this->mysql('mlite_duitku')
        ->join('pasien', 'pasien.no_rkm_medis=mlite_duitku.no_rkm_medis')
        ->desc('id')
        ->limit(10)
        ->toArray();

      return $this->draw('payment.duitku.html', [
        'paymentduitku' => $return
      ]);

    }

    protected function mysql($table = NULL)
    {
        return new MySQL($table);
    }

}
