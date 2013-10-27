// Required Libs
import java.util.Scanner;
import java.text.DecimalFormat;

// Program start
public class A3
{
	// Main
	public static void main(String[] args)
	{
		// Define working variables
		String userInput;
		double listPrice;
		int stateTaxPennies;
		int countryTaxPennies;
		int listPricePennies;
		int totalPennies;
		Scanner input = new Scanner(System.in);	
		DecimalFormat money = new DecimalFormat("$###,###,###,###,###,###,##0.00");
	
		// Ask user for input
		System.out.println("Please enter purchase price (ie: 12.43): ");

		// Capture standerd input 
		userInput = input.next();

		// Valid input
		if (validatePrice(userInput))
		{
			// Convert input string to double
			listPrice = Double.parseDouble(userInput);

			// Convert list price into pennies
			listPricePennies = toPennies(listPrice);

			// Calculate state tax in pennies (rounded to the nearest penny)
			stateTaxPennies = nearestInt(listPricePennies * 0.04);

			// Calculate country tax in pennies (rounded to the nearest penny)
			countryTaxPennies = nearestInt(listPricePennies * 0.02);
	
			// Calculate total
			totalPennies = listPricePennies + stateTaxPennies + countryTaxPennies;

			// Display total
			System.out.println("\nListPrice: "+money.format((double)listPricePennies/100.00)+"\nState Tax: "+money.format((double)stateTaxPennies/100.00)+"\nCountry Tax: "+money.format((double)countryTaxPennies/100.00)+"\nTotal: "+money.format((double)totalPennies/100.00));
		}
		// Invalid input
		else
		{
			// Display error and exit 
			System.out.println("\nSorry, but it looks like you entered an invalid price. Please try again. Make sure are entering only the price with no formatting like dollar signs and commas.\n");
		}
	}

	// Function to validate input
	public static boolean validatePrice(String price)
	{
		try
		{
			Double.parseDouble(price);
			return true;
		}
		catch (Exception e)
		{
			return false;
		}
	}

	// Function to convert dollar amounts (ie: $12.06) into pennies
	public static int toPennies(double amount)
	{
		// Working variables
		double cents;
		int centPennies; 
		int dollars;

		// Get number of whole dollars
		dollars = (int)amount;

		// Get remaining decimal
		cents = amount - dollars;

		// Shift decimal point two places to the right
		cents = cents * 100;

		// Round to the nearest penny
		centPennies = nearestInt(cents);
		
		// Convert whole dollars to pennies, add cent pennies, return
		return dollars * 100 + centPennies;
	}

	// Function to round decimal numbers to the nearest integer
	public static int nearestInt(double amount)
	{
		// If fraction is greater than or equal to .5 round up
		if (amount - (int)amount >= .5)
		{
			return (int)amount + 1;
		}
		// Otherwise round down
		else
		{
			return (int)amount;
		}
	}
}
