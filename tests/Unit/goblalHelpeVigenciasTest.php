<?php

namespace Tests\Unit;

use DateTime;
use PHPUnit\Framework\TestCase;

class goblalHelpeVigenciasTest extends TestCase
{   

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_CursoAntesCambioCurso()
    {
        $iniCurso = getAnoIniCursoEscolar( new DateTime('2020-08-29') );
        $this->assertEquals('2019', $iniCurso);
        // $this->assertTrue(true);
    }

    public function test_CursoAntesCambioCurso_getFormattedCursoEscolar()
    {
        $date = new DateTime('2020-08-29');
        $curso = getFormattedCursoEscolar( $date->getTimeStamp());
        $this->assertEquals('2019/2020', $curso);
        // $this->assertTrue(true);
    }
    public function test_CursoDiaCambioCurso_getFormattedCursoEscolar()
    {
        $date = new DateTime('2020-08-30');
        $curso = getFormattedCursoEscolar( $date->getTimeStamp());
        $this->assertEquals('2020/2021', $curso);
        // $this->assertTrue(true);
    }

    public function test_CursoDespuesCambioCurso_getFormattedCursoEscolar()
    {
        $date = new DateTime('2020-08-31');
        $curso = getFormattedCursoEscolar( $date->getTimeStamp());
        $this->assertEquals('2020/2021', $curso);
        // $this->assertTrue(true);
    }

     /**
     * @dataProvider getFormattedCursoEscolarProvider
     */
    public function test_getFormattedCursoEscolar(string $dateStr, string $expectedCurso): void
    {
        $date = new DateTime($dateStr);
        $curso = getFormattedCursoEscolar( $date->getTimeStamp());
        $this->assertEquals($curso, $expectedCurso);        
    }

    public function getFormattedCursoEscolarProvider(): array
    {
        $arr = [];
        foreach (range(1,7) as $mes) {
            $arr[]= ['2022-'.$mes.'-15','2021/2022'];
            $arr[]= ['2022-'.$mes.'-1', '2021/2022'];
            $arr[]= ['2022-'.$mes.'-30','2021/2022'];
        }
        $arr[]= ['2022-8-29','2021/2022'];
        $arr[]= ['2022-8-30','2022/2023'];
        $arr[]= ['2022-8-31','2022/2023'];
        foreach (range(9,12) as $mes) {
            $arr[]= ['2022-'.$mes.'-1', '2022/2023'];
            $arr[]= ['2022-'.$mes.'-15','2022/2023'];
            $arr[]= ['2022-'.$mes.'-30','2022/2023'];
        }
        return $arr;
    }

    /**
     * @dataProvider getAnoIniCursoEscolarProvider
     */
    public function test_getAnoIniCursoEscolar($dateStr, $iniCursoExpected)
    {
        $iniCurso = getAnoIniCursoEscolar( new DateTime($dateStr) );
        $this->assertEquals($iniCursoExpected, $iniCurso);
        // $this->assertTrue(true);
    }
    public function getAnoIniCursoEscolarProvider(): array
    {
        $arr = [];
        foreach (range(1,7) as $mes) {
            $arr[]= ['2022-'.$mes.'-1', '2021'];
            $arr[]= ['2022-'.$mes.'-15','2021'];
            $arr[]= ['2022-'.$mes.'-30','2021'];
        }
        $arr[]= ['2022-8-29','2021'];
        $arr[]= ['2022-8-30','2022'];
        $arr[]= ['2022-8-31','2022'];
        foreach (range(9,12) as $mes) {
            $arr[]= ['2022-'.$mes.'-1', '2022'];
            $arr[]= ['2022-'.$mes.'-15','2022'];
            $arr[]= ['2022-'.$mes.'-30','2022'];
        }
        return $arr;
    }


}
