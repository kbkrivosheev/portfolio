<?php

declare(strict_types=1);

namespace app\models\Competition;

use app\models\Competition\Forms\Base as BaseForm;
use app\models\Competition\Forms\Characteristics;
use app\models\Competition\Forms\ContainingIndicators;
use app\models\Competition\Forms\Expenses;
use app\models\Competition\Forms\NumericIndicators;
use app\models\Competition\Forms\Qualifications;
use app\models\Competition\Forms\Requests;
use Yii;

final class Handler
{
    private const PRECISION = 2;
    private const PERCENTAGE_COEFFICIENT = 0.01;
    private const HUNDRED = 100;
    private const ZERO = 0;
    public const YES = 'В наличии';
    public const NO = 'Отсутствует';
    /**
     * @var BaseForm
     */
    public BaseForm $form;
    private array $requestsPrices;
    private array $requestsExpenses;
    private array $characteristicsIndicators;
    private array $qualificationsIndicators;


    public static function create(array $params): self
    {
        return Yii::$container->get(static::class, [], $params);
    }

    /**
     * @return array
     */
    public function handle(): array
    {
        $result = [];
        if (empty($this->form->requests)) {
            return $result;
        }
        $this->calculate();

        $hasPriceGradeLessThanZero = false;
        foreach ($this->form->requests as $request) {
            $priceGrade = $this->getPriceGrade($request->price);
            if ($priceGrade < 0) {
                $hasPriceGradeLessThanZero = true;
            }
            $result[$request->id] = [
                'prices' => $this->getPriceData($request, $priceGrade),
                'expenses' => $this->getExpensesData($request),
                'characteristics' => $this->getAllCharacteristicsData($request),
                'qualifications' => $this->getAllQualificationsData($request)
            ];
            $result[$request->id]['finalGrade'] = $this->getFinalGradeData($result[$request->id]);
        }

        if ($hasPriceGradeLessThanZero) {
            foreach ($this->form->requests as $request) {
                $result[$request->id]['prices'] = $this->getPriceData(
                    $request,
                    $this->getPriceGradeGreaterThanZero($request->price)
                );
                $result[$request->id]['finalGrade'] = $this->getFinalGradeData($result[$request->id]);
            }
        }

        return $result;
    }

    /**
     * @return void
     */
    private function calculate(): void
    {
        $this->setRequestsPrices();
        $this->setRequestsExpenses();
        $this->setCharacteristicsIndicators();
        $this->setQualificationsIndicators();
    }

    /**
     * @param array $multiArray
     * @param string $name
     * @return array
     */
    private function getArrayValues(array $multiArray, string $name): array
    {
        $result = [];
        array_walk_recursive($multiArray, static function ($v, $k) use (&$result, $name) {
            if ($k == $name) {
                $result[] = $v;
            }
        });
        return $result;
    }

    /**
     * @param array $multiArray
     * @return float
     */
    private function getIndicatorsGrade(array $multiArray): float
    {
        return round(array_sum($this->getArrayValues($multiArray, 'total')), self::PRECISION);
    }

    /**
     * @param array $multiArray
     * @return float
     */
    private function getTotalIndicatorsGrade(array $multiArray): float
    {
        return round(array_sum($this->getArrayValues($multiArray, 'indicatorsTotal')), self::PRECISION);
    }

    /**
     * @param float|null $grade
     * @param float $criteria
     * @return float
     */
    private function getTotal(?float $grade, float $criteria): float
    {
        return !is_null($grade) ? round($grade * $criteria * self::PERCENTAGE_COEFFICIENT, self::PRECISION) : 0;
    }

    /**
     * @param array $parameters
     * @param string $id
     * @param string $name
     * @return mixed
     */
    private function getParameterValue(array $parameters, string $id, string $name)
    {
        $key = array_search($id, array_column($parameters, 'id'));
        return $parameters[$key]->$name;
    }

    /**
     * @param array $indicators
     * @return array
     */
    private function getNotNumericIndicatorsData(array $indicators): array
    {
        $result = [];
        foreach ($indicators as $indicator) {
            $result[$indicator->id] = [
                'name' => $indicator->name,
                'offer' => 'Не числовое',
                'grade' => $indicator->value,
                'total' => $this->getTotal($indicator->value, $indicator->criteria)
            ];
        }
        return $result;
    }


    /** start Price */

    /**
     * @return void
     */
    private function setRequestsPrices(): void
    {
        $this->requestsPrices = array_column($this->form->requests, 'price');
    }

    /**
     * @param Requests $request
     * @return array
     */
    private function getPriceData(Requests $request, float $priceGrade): array
    {
        $criteria = $request->isCriteriaReduced ? 10 : $this->form->parameters->criteria->contractPrice;
        return [
            'offer' => $request->price,
            'grade' => $priceGrade,
            'total' => $this->getTotal($priceGrade, $criteria)
        ];
    }

    /**
     * @param float $price
     * @return float
     */
    private function getPriceGrade(?float $price = null): float
    {
        if (!$price) {
            return self::ZERO;
        }
        $nmck = $this->form->parameters->nmck;
        $min = min($this->requestsPrices);
        $absMin = abs($min);

        if (!$min) {
            return self::HUNDRED;
        }

        if ($min > 0) {
            return round(
                self::HUNDRED - (($price - $min) / $min * self::HUNDRED),
                self::PRECISION
            );
        }

        if ($price > 0) {
            return round(
                self::HUNDRED - (($price + $absMin) / ($nmck + $absMin)) * self::HUNDRED,
                self::PRECISION
            );
        }

        return round(
            self::HUNDRED - (($absMin - abs($price)) / ($nmck + $absMin)) * self::HUNDRED,
            self::PRECISION
        );
    }

    private function getPriceGradeGreaterThanZero(?float $price = null): float
    {
        if (!$price) {
            return self::ZERO;
        }

        $nmck = $this->form->parameters->nmck;
        return round(
            ($nmck - $price) * self::HUNDRED / ($nmck - min($this->requestsPrices)),
            self::PRECISION
        );
    }
    /** end Price */

    /** start Expenses */
    /**
     * @return void
     */
    private function setRequestsExpenses(): void
    {
        if (!$this->form->expenses) {
            return;
        }
        foreach ($this->form->requests as $requests) {
            foreach ($requests->expenses as $expense) {
                $this->requestsExpenses[$expense->id][] = $expense->value;
            }
        }
    }

    /**
     * @param Requests $request
     * @return array
     */
    private function getExpensesData(Requests $request): array
    {
        $result = [];
        if (!$request->expenses) {
            return $result;
        }

        foreach ($request->expenses as $expense) {
            $expenseGrade = $this->getExpenseGrade($expense);
            $result[$expense->id] = [
                'name' => $expense->name,
                'offer' => $expense->value,
                'grade' => $expenseGrade,
                'total' => $this->getExpenseTotal($expenseGrade, $expense->id),
            ];
        }
        $criteria = $this->form->parameters->criteria->expense;
        $result['indicatorsGrade'] = $indicatorsGrade = $this->getIndicatorsGrade($result);
        $result['indicatorsTotal'] = $this->getTotal($indicatorsGrade, $criteria);
        return $result;
    }

    /**
     * @param Expenses $expense
     * @return float
     */
    private function getExpenseGrade(Expenses $expense): float
    {
        $max = max($this->requestsExpenses[$expense->id]);
        $min = min($this->requestsExpenses[$expense->id]);
        if ($max - $min == 0) {
            return 100;
        }
        return round(
            (($max - $expense->value) * self::HUNDRED) / ($max - $min),
            self::PRECISION
        );
    }

    /**
     * @param float $expenseGrade
     * @param string $id
     * @return float
     */
    private function getExpenseTotal(float $expenseGrade, string $id): float
    {
        $key = array_search($id, array_column($this->form->expenses, 'id'));
        return round(
            $expenseGrade * $this->form->expenses[$key]->criteria * self::PERCENTAGE_COEFFICIENT,
            self::PRECISION
        );
    }
    /** end Expenses */


    /** start Characteristics */

    /**
     * @return void
     */
    private function setCharacteristicsIndicators(): void
    {
        if (!$this->form->characteristics) {
            return;
        }
        foreach ($this->form->requests as $request) {
            foreach ($request->characteristics as $characteristic) {
                foreach ($characteristic->numericIndicators as $numericIndicator) {
                    $this->characteristicsIndicators[$numericIndicator->id][] = $numericIndicator->value;
                }
            }
        }
    }

    /**
     * @param Requests $request
     * @return array
     */
    private function getAllCharacteristicsData(Requests $request): array
    {
        $result = [];
        if (empty($request->characteristics)) {
            return $result;
        }
        $criteria = $this->form->parameters->criteria->characteristic;
        foreach ($request->characteristics as $characteristic) {
            $result[$characteristic->id] = $this->getCharacteristicData($characteristic);
        }
        $result['totalIndicatorsGrade'] = $totalIndicatorsGrade = $this->getTotalIndicatorsGrade($result);
        $result['totalCharacteristicsGrade'] = $this->getTotal($totalIndicatorsGrade, $criteria);
        return $result;
    }

    /**
     * @param Characteristics $characteristic
     * @return array
     */
    private function getCharacteristicData(Characteristics $characteristic): array
    {
        $result = [];
        $criteria = $this->getParameterValue($this->form->characteristics, $characteristic->id, 'criteria');

        if ($characteristic->numericIndicators) {
            $result['numericIndicators'] = $this->getCharacteristicNumericIndicatorsData(
                $characteristic->numericIndicators
            );
        }
        if ($characteristic->notNumericIndicators) {
            $result['notNumericIndicators'] = $this->getNotNumericIndicatorsData($characteristic->notNumericIndicators);
        }
        if ($characteristic->containingIndicators) {
            $result['containingIndicators'] = $this->getCharacteristicsContainingIndicatorsData(
                $characteristic->containingIndicators
            );
        }

        $result['indicatorsGrade'] = $indicatorsGrade = $this->getIndicatorsGrade($result);
        $result['indicatorsTotal'] = $this->getTotal($indicatorsGrade, $criteria);
        return $result;
    }

    /**
     * @param NumericIndicators[] $indicators
     * @return array
     */
    private function getCharacteristicNumericIndicatorsData(array $indicators): array
    {
        $result = [];
        foreach ($indicators as $indicator) {
            $result[$indicator->id] = [
                'name' => $indicator->name,
                'offer' => $indicator->value,
                'grade' => $indicatorGrade = $this->getCharacteristicNumericIndicatorGrade($indicator),
                'total' => $this->getTotal($indicatorGrade, $indicator->criteria)
            ];
        }
        return $result;
    }

    /**
     * @param NumericIndicators $indicator
     * @return float
     */
    private function getCharacteristicNumericIndicatorGrade(NumericIndicators $indicator): float
    {
        $indicatorParams = $this->getCharacteristicsNumericIndicatorParams($indicator->id);
        $min = $indicatorParams->minValue ?? min($this->characteristicsIndicators[$indicator->id]);
        $max = $indicatorParams->maxValue ?? max($this->characteristicsIndicators[$indicator->id]);
        $isMaximumValueBest = $indicatorParams->isMaximumValueBest;
        if ($indicatorParams->minValue > 0 && $indicatorParams->minValue > $indicator->value) {
            return $isMaximumValueBest ? self::ZERO : self::HUNDRED;
        }

        if ($indicatorParams->maxValue > 0 && $indicator->value >= $indicatorParams->maxValue) {
            return $isMaximumValueBest ? self::HUNDRED : self::ZERO;
        }

        if ($max - $min == self::ZERO) {
            return self::HUNDRED;
        }
        $result = $isMaximumValueBest ? (($indicator->value - $min) * self::HUNDRED) / ($max - $min) :
            (($max - $indicator->value) * self::HUNDRED) / ($max - $min);
        return min(round(max($result, self::ZERO), self::PRECISION), self::HUNDRED);
    }

    /**
     * @param string $indicatorId
     * @return NumericIndicators
     */
    private function getCharacteristicsNumericIndicatorParams(string $indicatorId): ?NumericIndicators
    {
        foreach ($this->form->characteristics as $characteristic) {
            $key = array_search($indicatorId, array_column($characteristic->numericIndicators, 'id'));
            break;
        }
        return $characteristic->numericIndicators[$key]??null;
    }

    /**
     * @param string $indicatorId
     * @return bool
     */
    private function getIsContainingCharacteristicsContainingIndicator(string $indicatorId): bool
    {
        foreach ($this->form->characteristics as $characteristic) {
            $result = $this->getParameterValue($characteristic->containingIndicators, $indicatorId, 'isContaining');
            if (is_bool($result)) {
                return $result;
            }
        }
    }

    /**
     * @param ContainingIndicators [] $indicators
     * @return array
     */
    private function getCharacteristicsContainingIndicatorsData(array $indicators): array
    {
        $result = [];
        foreach ($indicators as $indicator) {
            $isContaining = $this->getIsContainingCharacteristicsContainingIndicator($indicator->id);
            if ($isContaining) {
                $grade = $indicator->value ? self::HUNDRED : self::ZERO;
            } else {
                $grade = $indicator->value ? self::ZERO : self::HUNDRED;
            }
            $result[$indicator->id] = [
                'name' => $indicator->name,
                'offer' => $indicator->value ? self::YES : self::NO,
                'grade' => $grade,
                'total' => $this->getTotal($grade, $indicator->criteria)
            ];
        }
        return $result;
    }

    /** end Characteristics */

    /** start Qualifications */

    /**
     * @return void
     */
    private function setQualificationsIndicators(): void
    {
        if (!$this->form->qualifications) {
            return;
        }
        foreach ($this->form->requests as $request) {
            foreach ($request->qualifications as $qualifications) {
                foreach ($qualifications->numericIndicators as $numericIndicator) {
                    $this->qualificationsIndicators[$numericIndicator->id][] = $numericIndicator->value;
                }
            }
        }
    }

    /**
     * @param Requests $request
     * @return array
     */
    private function getAllQualificationsData(Requests $request): array
    {
        $result = [];
        if (empty($request->qualifications)) {
            return $result;
        }
        $criteria = $this->form->parameters->criteria->qualification;

        foreach ($request->qualifications as $qualification) {
            $result[$qualification->id] = $this->getQualificationData($qualification);
        }
        $result['totalIndicatorsGrade'] = $totalIndicatorsGrade = $this->getTotalIndicatorsGrade($result);
        $result['totalQualificationsGrade'] = $this->getTotal($totalIndicatorsGrade, $criteria);
        return $result;
    }

    /**
     * @param Qualifications $qualification
     * @return array
     */
    private function getQualificationData(Qualifications $qualification): array
    {
        $result = [];
        $criteria = $this->getParameterValue($this->form->qualifications, $qualification->id, 'criteria');
        if ($qualification->numericIndicators) {
            $result['numericIndicators'] = $this->getQualificationNumericIndicatorsData(
                $qualification->numericIndicators
            );
        }
        if ($qualification->notNumericIndicators) {
            $result['notNumericIndicators'] = $this->getNotNumericIndicatorsData($qualification->notNumericIndicators);
        }
        if ($qualification->containingIndicators) {
            $result['containingIndicators'] = $this->getQualificationContainingIndicatorsData(
                $qualification->containingIndicators
            );
        }

        $result['indicatorsGrade'] = $indicatorsGrade = $this->getIndicatorsGrade($result);
        $result['indicatorsTotal'] = $this->getTotal($indicatorsGrade, $criteria);
        return $result;
    }

    /**
     * @param array $indicators
     * @return array
     */
    private function getQualificationNumericIndicatorsData(array $indicators): array
    {
        $result = [];
        foreach ($indicators as $indicator) {
            $result[$indicator->id] = [
                'name' => $indicator->name,
                'offer' => $indicator->value,
                'grade' => $indicatorGrade = $this->getQualificationNumericIndicatorGrade($indicator),
                'total' => $this->getTotal($indicatorGrade, $indicator->criteria)
            ];
        }
        return $result;
    }

    /**
     * @param NumericIndicators $indicator
     * @return float
     */
    private function getQualificationNumericIndicatorGrade(NumericIndicators $indicator): float
    {
        $indicatorParams = $this->getQualificationsNumericIndicatorParams($indicator->id);
        $min = $indicatorParams->minValue ?? min($this->qualificationsIndicators[$indicator->id]);
        $max = $indicatorParams->maxValue ?? max($this->qualificationsIndicators[$indicator->id]);
        $isMaximumValueBest = $indicatorParams->isMaximumValueBest;
        if ($indicatorParams->minValue > 0 && $indicatorParams->minValue > $indicator->value) {
            return $isMaximumValueBest ? self::ZERO : self::HUNDRED;
        }
        if ($indicatorParams->maxValue > 0 && $indicator->value >= $indicatorParams->maxValue) {
            return $isMaximumValueBest ? self::HUNDRED : self::ZERO;
        }
        if ($max - $min == 0) {
            return self::HUNDRED;
        }
        $result = $isMaximumValueBest ? (($indicator->value - $min) * self::HUNDRED) / ($max - $min) :
            (($max - $indicator->value) * self::HUNDRED) / ($max - $min);
        return min(round(max($result, self::ZERO), self::PRECISION), self::HUNDRED);
    }

    /**
     * @param string $indicatorId
     * @return NumericIndicators
     */
    private function getQualificationsNumericIndicatorParams(string $indicatorId): NumericIndicators
    {
        foreach ($this->form->qualifications as $qualification) {
            $key = array_search($indicatorId, array_column($qualification->numericIndicators, 'id'));
            return $qualification->numericIndicators[$key];
        }
    }

    /** end Qualifications */

    /**
     * @param array $response
     * @return array
     */
    private function getFinalGradeData(array $response): array
    {
        $result = [
            'price' => $response['prices']['total'],
            'expense' => $response['expenses']['indicatorsTotal'],
            'characteristic' => $response['characteristics']['totalCharacteristicsGrade'],
            'qualification' => $response['qualifications']['totalQualificationsGrade'],
        ];
        $result['total'] = round(array_sum($result), self::PRECISION);
        return $result;
    }

    /**
     * @param ContainingIndicators [] $containingIndicators
     * @return array
     */
    private function getQualificationContainingIndicatorsData(array $containingIndicators): array
    {
        $result = [];
        foreach ($containingIndicators as $indicator) {
            $isContaining = $this->getIsContainingQualificationsContainingIndicator($indicator->id);
            if ($isContaining) {
                $grade = $indicator->value ? self::HUNDRED : self::ZERO;
            } else {
                $grade = $indicator->value ? self::ZERO : self::HUNDRED;
            }

            $result[$indicator->id] = [
                'name' => $indicator->name,
                'offer' => $indicator->value ? self::YES : self::NO,
                'grade' => $grade,
                'total' => $this->getTotal($grade, $indicator->criteria)
            ];
        }
        return $result;
    }

    /**
     * @param string $indicatorId
     * @return mixed|void
     */
    private function getIsContainingQualificationsContainingIndicator(string $indicatorId): bool
    {
        foreach ($this->form->qualifications as $qualification) {
            $result = $this->getParameterValue($qualification->containingIndicators, $indicatorId, 'isContaining');
            if (is_bool($result)) {
                return $result;
            }
        }
    }


}