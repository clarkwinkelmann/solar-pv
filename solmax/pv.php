<?php

class SolarMaxPV {
	
	protected $ip;
	protected $port;
	protected $addr;
	
	protected $timeout = 5;
	
	protected $socket = null;
	protected $query_time = null;
	
	protected $status = array();
	
	/**
	 * Result types
	 */
	const SOFTWARE_VERSION = 2;
	
	const OPHOURS = 23;
	const ENERGY_TODAY = 24;
	const ENERGY_YESTERDAY = 25;
	const ENERGY_MONTH_THIS = 26;
	const ENERGY_MONTH_LAST = 27;
	const ENERGY_YEAR_THIS = 28;
	const ENERGY_YEAR_LAST = 29;
	const ENERGY_TOTAL = 30;
	const LANGUAGE = 31;
	const DC_VOLTAGE_NOW = 32;
	const AC_VOLTAGE_NOW = 33;
	const DC_CURRENT_NOW = 34;
	const AC_CURRENT_NOW = 35;
	const AC_POWER_NOW = 36;
	const POWER_INSTALLED = 37;
	const PERCENT_LOAD_NOW = 38;
	const STARTUPS = 39;
	
	const TEMP_HEAT_SINK_NOW = 45;
	const AC_FREQ_NOW = 46;
	
	protected $cmd = array();
	
	/**
	 * @param {String} $ip
	 * @param {int} $port
	 * @param {int} $addr
	 */
	public function __construct($ip, $port, $addr) {
		// Populates the commands list
		// Because we cannot initialise the array with anonymous functions, see http://stackoverflow.com/q/9186782/3133038
		$this->cmd = array(
			0                        => array( 'desc' => 'Address',                   'name' => 'ADR', 'convert' => function($i){ return hexdec($i); } ), # 0
			1                        => array( 'desc' => 'Type',                      'name' => 'TYP', 'convert' => function($i) { return "0x" . $i; } ), # 1
			self::SOFTWARE_VERSION   => array( 'desc' => 'Software version',          'name' => 'SWV', 'convert' => function($i){ return sprintf("%1.1f", hexdec($i) / 10 ); } ), # 2
			3                        => array( 'desc' => 'Date day',                  'name' => 'DDY', 'convert' => function($i){ return hexdec($i); } ), # 3
			4                        => array( 'desc' => 'Date month',                'name' => 'DMT', 'convert' => function($i){ return hexdec($i); } ), # 4
			5                        => array( 'desc' => 'Date year',                 'name' => 'DYR', 'convert' => function($i){ return hexdec($i); } ), # 5
			6                        => array( 'desc' => 'Time hours',                'name' => 'THR', 'convert' => function($i){ return hexdec($i); } ), # 6
			7                        => array( 'desc' => 'Time minutes',              'name' => 'TMI', 'convert' => function($i){ return hexdec($i); } ), # 7
			8                        => array( 'desc' => '???Error 1, number???',     'name' => 'E11', 'convert' => function($i){ return hexdec($i); } ), # 8
			9                        => array( 'desc' => '???Error 1, day???',        'name' => 'E1D', 'convert' => function($i){ return hexdec($i); } ), # 9
			10                       => array( 'desc' => '???Error 1, month???',      'name' => 'E1M', 'convert' => function($i){ return hexdec($i); } ), # 10
			11                       => array( 'desc' => '???Error 1, hour???',       'name' => 'E1h', 'convert' => function($i){ return hexdec($i); } ), # 11
			12                       => array( 'desc' => '???Error 1, minute???',     'name' => 'E1m', 'convert' => function($i){ return hexdec($i); } ), # 12
			13                       => array( 'desc' => '???Error 2, number???',     'name' => 'E21', 'convert' => function($i){ return hexdec($i); } ), # 13
			14                       => array( 'desc' => '???Error 2, day???',        'name' => 'E2D', 'convert' => function($i){ return hexdec($i); } ), # 14
			15                       => array( 'desc' => '???Error 2, month???',      'name' => 'E2M', 'convert' => function($i){ return hexdec($i); } ), # 15
			16                       => array( 'desc' => '???Error 2, hour???',       'name' => 'E2h', 'convert' => function($i){ return hexdec($i); } ), # 16
			17                       => array( 'desc' => '???Error 2, minute???',     'name' => 'E2m', 'convert' => function($i){ return hexdec($i); } ), # 17
			18                       => array( 'desc' => '???Error 3, number???',     'name' => 'E31', 'convert' => function($i){ return hexdec($i); } ), # 18
			19                       => array( 'desc' => '???Error 3, day???',        'name' => 'E3D', 'convert' => function($i){ return hexdec($i); } ), # 19
			20                       => array( 'desc' => '???Error 3, month???',      'name' => 'E3M', 'convert' => function($i){ return hexdec($i); } ), # 20
			21                       => array( 'desc' => '???Error 3, hour???',       'name' => 'E3h', 'convert' => function($i){ return hexdec($i); } ), # 21
			22                       => array( 'desc' => '???Error 3, minute???',     'name' => 'E3m', 'convert' => function($i){ return hexdec($i); } ), # 22
			self::OPHOURS            => array( 'desc' => 'Operating hours',           'name' => 'KHR', 'convert' => function($i){ return hexdec($i); } ), # 23
			self::ENERGY_TODAY       => array( 'desc' => 'Energy today [Wh]',         'name' => 'KDY', 'convert' => function($i){ return (hexdec($i) * 100); } ), # 24
			self::ENERGY_YESTERDAY   => array( 'desc' => 'Energy yesterday [kWh]',    'name' => 'KLD', 'convert' => function($i){ return (hexdec($i) * 100); } ), # 25
			self::ENERGY_MONTH_THIS  => array( 'desc' => 'Energy this month [kWh]',   'name' => 'KMT', 'convert' => function($i){ return hexdec($i); } ), # 26
			self::ENERGY_MONTH_LAST  => array( 'desc' => 'Energy last monh [kWh]',    'name' => 'KLM', 'convert' => function($i){ return hexdec($i); } ), # 27
			self::ENERGY_YEAR_THIS   => array( 'desc' => 'Energy this year [kWh]',    'name' => 'KYR', 'convert' => function($i){ return hexdec($i); } ), # 28
			self::ENERGY_YEAR_LAST   => array( 'desc' => 'Energy last year [kWh]',    'name' => 'KLY', 'convert' => function($i){ return hexdec($i); } ), # 29
			self::ENERGY_TOTAL       => array( 'desc' => 'Energy total [kWh]',        'name' => 'KT0', 'convert' => function($i){ return hexdec($i); } ), # 30
			self::LANGUAGE           => array( 'desc' => 'Language',                  'name' => 'LAN', 'convert' => function($i){ return hexdec($i); } ), # 31
			self::DC_VOLTAGE_NOW     => array( 'desc' => 'DC voltage [mV]',           'name' => 'UDC', 'convert' => function($i){ return (hexdec($i) * 100); } ), # 32
			self::AC_VOLTAGE_NOW     => array( 'desc' => 'AC voltage [mV]',           'name' => 'UL1', 'convert' => function($i){ return (hexdec($i) * 100); } ), # 33
			self::DC_CURRENT_NOW     => array( 'desc' => 'DC current [mA]',           'name' => 'IDC', 'convert' => function($i){ return (hexdec($i) * 10); } ), # 34
			self::AC_CURRENT_NOW     => array( 'desc' => 'AC current [mA]',           'name' => 'IL1', 'convert' => function($i){ return (hexdec($i) * 10); } ), # 35
			self::AC_POWER_NOW       => array( 'desc' => 'AC power [mW]',             'name' => 'PAC', 'convert' => function($i){ return (hexdec($i) * 500); } ), # 36
			self::POWER_INSTALLED    => array( 'desc' => 'Power installed [mW]',      'name' => 'PIN', 'convert' => function($i){ return (hexdec($i) * 500); } ), # 37
			self::PERCENT_LOAD_NOW   => array( 'desc' => 'AC power [%]',              'name' => 'PRL', 'convert' => function($i){ return hexdec($i); } ), # 38
			self::STARTUPS           => array( 'desc' => 'Start ups',                 'name' => 'CAC', 'convert' => function($i){ return hexdec($i); } ), # 39
			40                       => array( 'desc' => '???',                       'name' => 'FRD', 'convert' => function($i){ return "0x" . $i; } ), # 40
			41                       => array( 'desc' => '???',                       'name' => 'SCD', 'convert' => function($i){ return "0x" . $i; } ), # 41
			42                       => array( 'desc' => '???',                       'name' => 'SE1', 'convert' => function($i){ return "0x" . $i; } ), # 42
			43                       => array( 'desc' => '???',                       'name' => 'SE2', 'convert' => function($i){ return "0x" . $i; } ), # 43
			44                       => array( 'desc' => '???',                       'name' => 'SPR', 'convert' => function($i){ return "0x" . $i; } ), # 44
			self::TEMP_HEAT_SINK_NOW => array( 'desc' => 'Temerature Heat Sink',      'name' => 'TKK', 'convert' => function($i){ return hexdec($i); } ), # 45
			self::AC_FREQ_NOW        => array( 'desc' => 'AC Frequency',              'name' => 'TNF', 'convert' => function($i){ return (hexdec($i) / 100); } ), # 46
			47                       => array( 'desc' => 'Operation State',           'name' => 'SYS', 'convert' => function($i){ return hexdec($i); } ), # 47
			48                       => array( 'desc' => 'Build number',              'name' => 'BDN', 'convert' => function($i){ return hexdec($i); } ), # 48
			49                       => array( 'desc' => 'Error-Code(?) 00',          'name' => 'EC00', 'convert' => function($i){ return hexdec($i); } ), # 49
			50                       => array( 'desc' => 'Error-Code(?) 01',          'name' => 'EC01', 'convert' => function($i){ return hexdec($i); } ), # 50
			51                       => array( 'desc' => 'Error-Code(?) 02',          'name' => 'EC02', 'convert' => function($i){ return hexdec($i); } ), # 51
			52                       => array( 'desc' => 'Error-Code(?) 03',          'name' => 'EC03', 'convert' => function($i){ return hexdec($i); } ), # 52
			53                       => array( 'desc' => 'Error-Code(?) 04',          'name' => 'EC04', 'convert' => function($i){ return hexdec($i); } ), # 53
			54                       => array( 'desc' => 'Error-Code(?) 05',          'name' => 'EC05', 'convert' => function($i){ return hexdec($i); } ), # 54
			55                       => array( 'desc' => 'Error-Code(?) 06',          'name' => 'EC06', 'convert' => function($i){ return hexdec($i); } ), # 55
			56                       => array( 'desc' => 'Error-Code(?) 07',          'name' => 'EC07', 'convert' => function($i){ return hexdec($i); } ), # 56
			57                       => array( 'desc' => 'Error-Code(?) 08',          'name' => 'EC08', 'convert' => function($i){ return hexdec($i); } ), # 57
		);
		
		$this->ip = $ip;
		$this->port = $port;
		$this->addr = $addr;
	}
	
	
	/**
	 * @return {int} ????
	 */
	protected function ping($timeout = 3) {
		$fsock = fsockopen($this->ip, $this->port, $errno, $errstr, $timeout);
		
		return $fsock;
	}
	
	protected function checksum16($msg) {
		# calculates the checksum 16 of the given string argument
		$bytes = unpack("C*", $msg);
		$sum = 0;
		foreach($bytes as $b) {
			$sum += $b;
			$sum = $sum % pow(2,16);
		}
		return $sum;
	}
	
	/**
	 * @param {Array} $questions
	 *
	 * @return {String}
	 */
	protected function mkmsg($questions) {
		# makes a message with the items in the given array as questions
		$src = 'FB';
		$dst = $this->addr;
		$dst = sprintf('%02X', $dst);
		$len = '00';
		$cs = '0000';
		$msg = is_array($questions) ? "64:" . implode(';', $questions) : "64:" . $questions;
		$len = strlen("{" . $src . ";" . $dst . ";" . $len . "|" . $msg . "|" . $cs . "}");
		$len = sprintf("%02X", $len);
		$cs = $this->checksum16($src . ";" . $dst . ";" . $len . "|" . $msg . "|");	
		$cs = sprintf("%04X", $cs);
		return "{" . $src . ";" . $dst . ";" . $len . "|" . $msg . "|" . $cs . "}";
	}
	
	protected function getsmparam($code) {
		if(!$this->isSocketOpen()) {
			throw new Exception('No socket open');
		}
		
		// TODO: exception
		$P_COMMAND = $this->cmd[$code];
		
		$P_HANDLE = $this->socket;
		$P_TIMEOUT = $this->timeout;
		$P_DEVADDR = $this->addr;
		
		$V_MSG = $this->mkmsg($P_COMMAND['name']);
		$V_RV = fwrite($P_HANDLE, $V_MSG);
		if(!$V_RV) die("Write error: $!");
		# Reading first 9 bytes
		$V_MSG = fread($P_HANDLE, 9);
		
		if(!preg_match("/([0-9A-F]{2});FB;([0-9A-F]{2})/",$V_MSG,$matches)) {
			flush();
			fclose($P_HANDLE);
			die("Invalid response from header");
		}
		
		if($matches[1] != $P_DEVADDR) {
			flush();
			fclose($P_HANDLE);
			die("wrong source address: {$matches[1]} != $P_DEVADDR");
		}
		$V_LEN = hexdec($matches[2]);
		$V_LEN -= 9; # header is already in
		$V_MSG = fread($P_HANDLE, $V_LEN);
		
		#Logic required here to separately test OPSTATES and return that value
		if(!preg_match('/^\|64:(\w{3})=([0-9A-F]+)\|([0-9A-F]{4})}$/',$V_MSG,$matches)) {
			flush();
			fclose($P_HANDLE);
			die("invalid response");
		}
		
		if($matches[1]!=$P_COMMAND['name']) {
			flush();
			fclose($P_HANDLE);
			die("wrong response");
		}
		
		$retval = $P_COMMAND['convert']($matches[2]);
		return $retval;
	}
	
	/**
	 * @return {bool} True if a socket is open
	 */
	protected function isSocketOpen() {
		return !is_null($this->socket);
	}
	
	/**
	 * Open the socket
	 */
	public function openSocket() {
		if($this->isSocketOpen()) {
			$this->closeSocket();
		}
		
		$socket = $this->ping();
		
		if($socket === false) {
			throw new Exception('Cannot connect to pv');
		}
		
		$this->socket = $socket;
	}
	
	/**
	 * Close the socket
	 */
	public function closeSocket() {
		if(!$this->isSocketOpen()) {
			throw new Exception('No open socket');
		}
		
		flush();
		fclose($this->socket);
		$this->socket = null;
	}
	
	/**
	 * @param {int} $code
	 */
	public function query($code) {
		$use_temporary_socket = !$this->isSocketOpen();
		if($use_temporary_socket) {
			$this->openSocket();
		}
		
		$value = null;
		
		try {
			$value = $this->getsmparam($code);
			$this->status[$code] = $value;
		} finally {
			if($use_temporary_socket) {
				$this->closeSocket();
			}
		}
		
		return $value;
	}
	
}

// Test

$pv = new SolarMaxPV('192.168.1.21', 12345, 1);

$pv->openSocket();

?>

<meta charset="utf-8">

<p>Puissance actuelle: <?= $pv->query(SolarMaxPV::AC_POWER_NOW)/1000 ?> W</p>
<p>Version logicielle: <?= $pv->query(SolarMaxPV::SOFTWARE_VERSION) ?></p>
<p>Température: <?= $pv->query(SolarMaxPV::TEMP_HEAT_SINK_NOW) ?> °C</p>

<?php

$pv->closeSocket();

?>