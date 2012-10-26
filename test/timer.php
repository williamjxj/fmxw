<?php
//http://php.net/manual/en/function.array-push.php
class timer
{
	private $start;
	private $end;

	public function timer()
	{
			$this->start = microtime(true);
	}

	public function Finish()
	{
			$this->end = microtime(true);
	}

	private function GetStart()
	{
			if (isset($this->start))
					return $this->start;
			else
					return false;
	}

	private function GetEnd()
	{
			if (isset($this->end))
					return $this->end;
			else
					return false;
	}

	public function GetDiff()
	{
			return $this->GetEnd() - $this->GetStart();
	}

	public function Reset()
	{
			$this->start = microtime(true);
	}
}

echo "Adding 100k elements to array with []\n\n";
$ta = array();
$test = new Timer();
for ($i = 0; $i < 100000; $i++)
{
        $ta[] = $i;
}
$test->Finish();
echo $test->GetDiff();

echo "\n\nAdding 100k elements to array with array_push\n\n";
$test->Reset();
for ($i = 0; $i < 100000; $i++)
{
        array_push($ta,$i);
}
$test->Finish();
echo $test->GetDiff();

echo "\n\nAdding 100k elements to array with [] 10 per iteration\n\n";
$test->Reset();
for ($i = 0; $i < 10000; $i++)
{
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
        $ta[] = $i;
}
$test->Finish();
echo $test->GetDiff();

echo "\n\nAdding 100k elements to array with array_push 10 per iteration\n\n";
$test->Reset();
for ($i = 0; $i < 10000; $i++)
{
        array_push($ta,$i,$i,$i,$i,$i,$i,$i,$i,$i,$i);
}
$test->Finish();
echo $test->GetDiff();
?>

Output

$ php5 arraypush.php
X-Powered-By: PHP/5.2.5
Content-type: text/html

Adding 100k elements to array with []

0.044686794281006

Adding 100k elements to array with array_push

0.072616100311279

Adding 100k elements to array with [] 10 per iteration

0.034690141677856

Adding 100k elements to array with array_push 10 per iteration

0.023932933807373