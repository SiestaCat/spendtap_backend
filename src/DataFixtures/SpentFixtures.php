<?php

namespace App\DataFixtures;

use App\Entity\Spent;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SpentFixtures extends Fixture
{
    private array $expenseDescriptions = [
        'Supermercado Carrefour', 'Gasolina Shell', 'Restaurante La Parrilla', 'Farmacia San Pablo',
        'Café Starbucks', 'Metro transporte', 'Cine Cineplex', 'Pizza Dominos',
        'Librería Gandhi', 'Taxi Uber', 'Dentista consulta', 'Peluquería Style',
        'Ferretería Home Depot', 'Ropa Zara', 'Electricidad CFE', 'Agua potable',
        'Internet Telmex', 'Seguro médico', 'Gimnasio SportCity', 'Lavandería Express',
        'Panadería La Espiga', 'Verdulería Central', 'Carnicería Premium', 'Pescadería Mariscos',
        'Zapatería Flexi', 'Juguetería Toy Story', 'Papelería Office Depot', 'Floristería Rosa',
        'Veterinaria Pets', 'Mecánico AutoFix', 'Banco comisión', 'Parking centro',
        'Bus urbano', 'Revista Proceso', 'Helados Häagen-Dazs', 'Barbería Vintage',
        'Óptica Devlyn', 'Tintorería Clean', 'Gasolinera Pemex', 'Mercado San Juan',
        'Hospital consulta', 'Clínica análisis', 'Laboratorio estudios', 'Radiografía dental',
        'Multa tránsito', 'Estacionamiento', 'Autopista peaje', 'Reparación celular'
    ];

    private array $incomeDescriptions = [
        'Salario mensual', 'Bonus trimestral', 'Freelance proyecto', 'Venta usados',
        'Intereses bancarios', 'Dividendos inversión', 'Reembolso seguro', 'Regalo cumpleaños',
        'Comisión ventas', 'Trabajo extra', 'Consultoría', 'Alquiler inmueble'
    ];

    private array $expenseCategories = [
        'Alimentación', 'Transporte', 'Salud', 'Entretenimiento', 'Hogar', 'Ropa',
        'Servicios', 'Educación', 'Belleza', 'Tecnología', 'Mascotas', 'Deportes',
        'Viajes', 'Regalos', 'Seguros', 'Impuestos', 'Mantenimiento', 'Otros'
    ];

    private array $incomeCategories = [
        'Salario', 'Bonus', 'Freelance', 'Ventas', 'Inversiones', 'Otros ingresos'
    ];

    public function load(ObjectManager $manager): void
    {
        $currentYear = 2026;
        $previousYear = 2022;

        // Generate data for 2 years
        for ($year = $previousYear; $year <= $currentYear; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                // Skip future months in current year
                if ($year === $currentYear && $month > 10) {
                    break;
                }

                $this->generateMonthlyData($manager, $year, $month);
            }
        }

        $manager->flush();
    }

    private function generateMonthlyData(ObjectManager $manager, int $year, int $month): void
    {
        $daysInMonth = \cal_days_in_month(CAL_GREGORIAN, $month, $year);
        
        // Generate 4 income records per month
        for ($i = 0; $i < 7; $i++) {
            $spent = new Spent();
            $spent->setDescription($this->getRandomItem($this->incomeDescriptions));
            $spent->setCategory($this->getRandomItem($this->incomeCategories));
            $spent->setAmount($this->generatePositiveAmount());
            
            $day = rand(1, $daysInMonth);
            $hour = rand(8, 18);
            $minute = rand(0, 59);
            $date = new \DateTime("{$year}-{$month}-{$day} {$hour}:{$minute}:00");
            
            $spent->setDate($date);
            $spent->setMonth($month);
            $spent->setYear($year);
            
            $manager->persist($spent);
        }

        // Generate 46 expense records per month (50 total - 4 incomes = 46 expenses)
        for ($i = 0; $i < 46; $i++) {
            $spent = new Spent();
            $spent->setDescription($this->getRandomItem($this->expenseDescriptions));
            $spent->setCategory($this->getRandomItem($this->expenseCategories));
            $spent->setAmount($this->generateNegativeAmount());
            
            $day = rand(1, $daysInMonth);
            $hour = rand(6, 23);
            $minute = rand(0, 59);
            $date = new \DateTime("{$year}-{$month}-{$day} {$hour}:{$minute}:00");
            
            $spent->setDate($date);
            $spent->setMonth($month);
            $spent->setYear($year);
            
            $manager->persist($spent);
        }
    }

    private function generatePositiveAmount(): string
    {
        // Income amounts between 500 and 5000
        $amount = rand(50000, 500000) / 100; // 500.00 to 5000.00
        return number_format($amount, 2, '.', '');
    }

    private function generateNegativeAmount(): string
    {
        // Expense amounts between -5 and -500
        $amount = rand(50, 50000) / 100; // 5.00 to 500.00
        return '-' . number_format($amount, 2, '.', '');
    }

    private function getRandomItem(array $items): string
    {
        return $items[array_rand($items)];
    }
}